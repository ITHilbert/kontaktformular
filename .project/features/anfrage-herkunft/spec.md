# Feature: Herkunft der Anfrage (Google Ads / UTM) mitsenden

## Ausgangslage
Die Marketing-Auswertung (Kosten je echter Anfrage, Regeln R2/R3 im Repo `ads-warehouse`) muss erkennen, ob eine Anfrage über eine Google-Ads-Anzeige kam. Heute geht die `gclid` verloren, sobald der Besucher eine zweite Seite aufruft. Deshalb ist die Herkunft bei allen bisher erfassten Anfragen „unbekannt“.
Lösung ohne Einwilligung: keine neuen Cookies, nichts im Browser speichern. Die Werte liegen in der serverseitigen Laravel-Session, die es ohnehin gibt.
Anlass: Dashboard-Task #85 (Projekt kontaktformular), Bezug #84 (`anfrage-bcc`).

## Akzeptanzkriterien
- **AK1 — Erfassen beim ersten Request:** Gegeben eine Session ohne gespeicherte Herkunft — Wenn ein Request über die Middleware-Gruppe `web` läuft — Dann stehen in der Session: die vorhandenen Query-Parameter `gclid`, `gbraid`, `wbraid`, `utm_source`, `utm_medium`, `utm_campaign`, `utm_term`, die Einstiegsseite als URL-dekodierter Pfad ohne Query (z. B. `/excel-programmierer`, `/über-uns` statt `/%C3%BCber-uns`) und vom `Referer`-Header nur der Host (z. B. `google.com`).
- **AK2 — First Touch:** Gegeben eine Session mit gespeicherter Herkunft — Wenn weitere Requests folgen, auch mit anderen oder neuen Parametern bzw. einem anderen Referrer — Dann bleiben die gespeicherten Werte unverändert.
- **AK3 — Speichern mit der Anfrage:** Wenn eine gültige Anfrage abgeschickt wird — Dann steht die Herkunft der Session in der neuen, nullable JSON-Spalte `herkunft` der Tabelle `kontaktformular` (Migration im Paket). Ist in der Session keine Herkunft gespeichert, bleibt die Spalte `null`, und die Anfrage wird trotzdem gespeichert und verschickt.
- **AK4 — Zeilen in der Mail:** Wenn die Mail `Anfrage` verschickt wird — Dann enthalten HTML- und Text-Teil direkt nach `Site:` die Zeilen `Herkunft:`, `Einstieg:`, `Referrer:` und `Klick-ID:`, z. B. im Text-Teil:
  ```
  Herkunft: google_ads
  Einstieg: /excel-programmierer
  Referrer: google.com
  Klick-ID: gclid
  ```
  Fehlt ein Wert für `Einstieg:` oder `Referrer:`, steht dort `-`.
- **AK5 — Einordnung:** `Herkunft:` ist
  - `google_ads`, wenn `gclid`, `gbraid` oder `wbraid` vorhanden ist oder `utm_source=google` und `utm_medium=cpc`;
  - sonst `organisch/sonstige`, wenn ein Referrer vorhanden ist;
  - sonst `direkt`.
- **AK6 — Klick-ID ohne Wert:** `Klick-ID:` nennt nur den Namen des vorhandenen Parameters (`gclid`, `gbraid` oder `wbraid`), sonst `-`. Der Wert der Klick-ID steht nie in der Mail, weder im HTML- noch im Text-Teil — auch nicht über `Site:`: Der Controller entfernt beim Speichern die Parameter `gclid`, `gbraid` und `wbraid` aus dem Formularwert `site` (Spalte `url`); alle übrigen Bestandteile der URL, auch `utm_*`, bleiben unverändert. *Ergänzt 2026-10-26 nach Review, freigegeben durch den User.*
- **AK7 — Kein neuer Cookie:** Wenn ein Request mit `?gclid=…&utm_source=…` gesendet wird — Dann setzt die Response genau dieselben Cookies (nach Namen) wie derselbe Request ohne diese Parameter. Das Paket schreibt nichts in `localStorage` oder `sessionStorage` und liefert dafür kein JavaScript aus.
- **AK8 — Bestehende Zeilen unverändert:** Die Zeilen `Nummer:`, `Datum:` und `Site:` im Text-Teil bleiben in Bezeichnung, Reihenfolge und Format unverändert; der Wert von `Site:` enthält keine Klick-ID-Parameter (AK6), sonst ist er der übermittelte Wert. *Angepasst 2026-10-26 nach Review, freigegeben durch den User.*
- **AK9 — Dokumentiert:** Die README beschreibt die Middleware (wird automatisch in die Gruppe `web` eingehängt), die erfassten Werte, die neuen Mail-Zeilen, die Spalte `herkunft` und den Hinweis für die Datenschutzerklärung der Site.

## Randfälle
1. Mehrere Klick-IDs gleichzeitig → `Klick-ID:` nennt die erste in der Reihenfolge `gclid`, `gbraid`, `wbraid`.
2. `utm_source`/`utm_medium` in anderer Schreibweise (`Google`, `CPC`) → zählt für AK5 wie `google`/`cpc`.
3. Der Referrer hat denselben Host wie der Request (interne Navigation, abgelaufene Session) → gilt als kein Referrer.
4. `Referer`-Header ohne gültigen Host (z. B. `android-app://…` ohne Host, Müll) → gilt als kein Referrer, kein Fehler.
5. Ein Parameter ist ein Array (`?gclid[]=x`) oder leer → wird ignoriert. Längere Werte als 255 Zeichen werden auf 255 gekürzt.
6. Der erste Request einer Session ist schon `POST /anfrage` → Herkunft wird aus diesem Request erfasst (Einstieg `/anfrage`) und mit der Anfrage gespeichert.
7. Request ohne Session (Route außerhalb `web`, z. B. `api`) → die Middleware tut nichts, kein Fehler.
8. Bestehende Datensätze → `herkunft` bleibt `null`, kein Nachbefüllen.
9. Session-Treiber `cookie` (`session.driver`) → die Middleware speichert nichts, weil die Session-Daten dann im Browser lägen. Die Anfrage wird mit `herkunft = null` gespeichert, die Mail-Zeilen zeigen `direkt` bzw. `-`. *Ergänzt durch den Moderator am 2026-10-26 (DSGVO-konforme Wahl, ohne Rückfrage nach Rule `entscheidungsbefugnis.md`).*

10. Werte aus dem Request mit Steuerzeichen (z. B. `%0A`, `%0D` im Pfad, in UTM-Werten, Klick-IDs oder im Formularwert `site`) → die Steuerzeichen und die Unicode-Zeilentrenner U+2028/U+2029 werden entfernt, bevor sie gespeichert oder in die Mail geschrieben werden; es entsteht keine zusätzliche Zeile (AK4). Jeder Parametername, den PHP als Klick-ID liest (z. B. `gcl%69d`, `+gclid`, `%20gclid`, `gclid%00`), gilt als Klick-ID, auch beim Entfernen aus `site` (AK6) — maßgeblich ist PHPs eigener Parser. *Erweitert 2026-10-26 nach dem dritten Review, freigegeben durch den User.* *Ergänzt 2026-10-26 nach dem zweiten Review, freigegeben durch den User („Fix als Bugfix gegen die bestehenden AKs“); `site` vom Moderator ergänzt.*

## Klarstellungen aus der Umsetzung
*Auslegungen der Coder, vom Moderator am 2026-10-26 in die Spec übernommen — vom User freigegeben am 2026-10-26.*
- **K1 (Randfall 9):** Erkannt wird der Cookie-Treiber am Session-Handler (`CookieSessionHandler`), damit zählen auch eigene Treibernamen, die ihn nutzen.
- **K2 (Randfall 5):** Werte werden vor der Prüfung auf „leer“ getrimmt; nur Leerzeichen = leer.
- **K3 (Randfall 4):** Der Referrer-Host wird kleingeschrieben und als Hostname geprüft (`FILTER_VALIDATE_DOMAIN`); IPv6-Literale und Hosts mit Umlauten in Unicode-Form gelten als kein Referrer.
- **K4 (AK2):** Die Herkunft bleibt nach dem Absenden in der Session; weitere Anfragen derselben Session bekommen dieselbe Herkunft.
- **K5 (Entscheidungsspielraum):** Fehlt `StartSession` in `web`, wird die Middleware angehängt; fehlt es in der Priorität, bleibt die Priorität unverändert. Unterklassen von `StartSession` zählen als `StartSession`.
- **K6 (AK1):** Ergibt das Dekodieren des Einstiegs kein gültiges UTF-8, bleibt er kodiert (sonst wäre er nicht als JSON speicherbar).
- **K7 (AK6):** Eine Klick-ID im Fragment (`#…`) von `site` bleibt stehen; `gclid[]=…` ist keine Klick-ID (Randfall 5); leere Paare (`&&`) bleiben erhalten.
- **K8 (Randfall 10):** In `site` werden rohe Steuerzeichen entfernt; prozentkodierte (`%0A`) bleiben kodiert stehen, weil `site` nicht dekodiert wird und so keine neue Zeile entsteht.

## Nicht-Ziele
- Den Wert der `gclid` für einen Offline-Conversion-Upload an Google weitergeben.
- Auswertung der Zeile `Herkunft:` im Lead-Skript (`ads-warehouse/ads_warehouse/leads/parsing.py`), das macht der Marketing-Agent.
- Die Datenschutzerklärungen der Sites ändern. Der Moderator prüft sie und schickt dem User einen Textvorschlag.
- Rollout auf die Sites und Release/Tag: nur nach Freigabe bzw. auf Ansage des User.
- Herkunft im Backend oder in Listen anzeigen.

## Entscheidungsspielraum
- Einordnung (AK5/AK6) an **einer** Stelle, z. B. in einer Klasse oder einem Value Object `Herkunft`, die Mail-Views nutzen sie nur. *Warum:* Die Regel darf nicht in zwei Views getrennt auseinanderlaufen.
- Name des Session-Schlüssels, Name der Middleware-Klasse und Aufbau des JSON wählt der Coder. Im JSON stehen nur die erfassten Werte aus AK1.
- Die Middleware wird im ServiceProvider in die Gruppe `web` **direkt nach `StartSession`** eingefügt (Gruppe und Middleware-Priorität), damit sie auch bei Abbruch durch CSRF, `auth` oder `throttle` erfasst (AK1). Middlewares, die andere Pakete in `web` einhängen (z. B. `userauth`), bleiben erhalten — am HTTP-Kernel nur per `afterResolving` ergänzen, nie einen bereits aufgelösten Kernel seine Gruppen auf den Router schreiben lassen. *Geändert 2026-10-26 nach Review/Optimierungslauf.*
- Keine neuen Pakete.
