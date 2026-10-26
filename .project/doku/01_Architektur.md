# Architekturübersicht: ITHilbert Kontaktformular

## Aufbau

Das Paket besteht aus einem Service Provider, einem Controller für Anfrage und Downloads, einer Middleware zur Herkunftserfassung, Value Objects für Herkunft und Seitenadresse, einem Eloquent-Model für Anfragen und einem Mailable für die Benachrichtigung. Die Geschäftslogik liegt im Controller; es gibt keine separate Service- oder Port-Schicht.

## Bausteine

| Baustein | Aufgabe |
|---|---|
| `KontaktformularServiceProvider` | Registriert Routen, Views, Übersetzungen, Migrationen und Config. Hängt `HerkunftErfassen` in die Gruppe `web` direkt nach `StartSession` (siehe [decisions/0002](decisions/0002-middleware-registrierung-web.md)). |
| `Controllers/KontaktformularController` | `anfrage()`: Spam-Prüfung, Validierung, Speichern, Herkunft aus der Session, Mail-Versand. `file_download()`: Anhang-Download mit Hash- und Namensprüfung. |
| `Middleware/HerkunftErfassen` | Merkt sich beim ersten Request einer Session die Herkunft (First Touch) im Session-Key `kontaktformular_herkunft`. |
| `ValueObjects/Herkunft` | Unveränderliches Objekt für die erfassten Werte. Liefert den Kanal (`kanal()`) und die erste Klick-ID (`klickId()`). |
| `ValueObjects/SiteAdresse` | Entfernt `gclid`, `gbraid` und `wbraid` aus der übermittelten Seitenadresse (`site`). |
| `Enums/HerkunftKanal` | Fachliche Kanäle: `google_ads`, `organisch/sonstige`, `direkt`. |
| `Models/Kontaktformular` | Eloquent-Model der Tabelle `kontaktformular`. Die Spalte `herkunft` wird als Array gecastet. |
| `Mail/Anfrage` | Mailable an `mailTo`. Setzt BCC aus `mailBcc` und die vier Herkunftszeilen. |

## Datenfluss Herkunft

1. Erster Request einer Session (z. B. `/?gclid=…`): `HerkunftErfassen` läuft direkt nach `StartSession`. Sie liest Query-Parameter, Pfad und Referrer-Host und legt das Ergebnis als Array in der Session unter `kontaktformular_herkunft` ab.
2. Weitere Requests derselben Session ändern diesen Wert nicht (First Touch). Beim Session-Treiber `cookie` wird nichts erfasst.
3. `POST anfrage`: Der Controller liest das Array aus der Session, normalisiert es über `Herkunft::fromArray()` und speichert es in der Spalte `herkunft`. Fehlt eine Herkunft, bleibt die Spalte `null`.
4. Die Seitenadresse aus dem Formularfeld `site` wird über `SiteAdresse::aus()->ohneKlickIds()` bereinigt und in der Spalte `url` gespeichert.
5. `Mail/Anfrage` liest `herkunft` aus dem Model und erzeugt daraus die Zeilen `Herkunft`, `Einstieg`, `Referrer` und `Klick-ID`. Die Zeilen stehen in HTML- und Text-Mail direkt nach `Site:`.

Die Begründung für die serverseitige Speicherung in der Session und den Verzicht auf Cookies steht in [decisions/0001](decisions/0001-herkunft-serverseitig-in-session.md).

## Herkunft im Code

- Kanal-Regel: Klick-ID oder `utm_source=google` mit `utm_medium=cpc` ergibt `google_ads`; sonst Referrer vorhanden ergibt `organisch/sonstige`; sonst `direkt`.
- Die Mail nennt nur den Namen der Klick-ID, nie den Wert.
- Details zu Erfassung und Mail-Ausgabe: `README.md`, Abschnitt [Herkunft der Anfrage](../../README.md#herkunft-der-anfrage-google-ads--utm).
