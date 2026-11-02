# Prüfung: mailversand (#316) — 2026-11-02

Geprüft: Paket kontaktformular `fix/mailversand` (3912254), Host `fix/kontaktformular-mailversand` (3ab75af), Review-Platz `/home/joerg/projekte/.worktrees/packages_dev/reviewer`. Feature-Diff = `origin/main...HEAD` im Paket (lokales `main` im Platz ist veraltet): `src/Controllers/KontaktformularController.php` (try/catch um den Versand), neu `src/Exceptions/AnfrageMailNichtVersendet.php`, README, Tests `tests/Feature/MailversandFehlerTest.php`.

Echter Lauf (eigener Server `php -S` auf freiem Port im Platz, `APP_ENV=testing`, DB `packages_dev_review_echtlauf_test`, ohne Browser per curl: Token über `GET /kontaktformular/csrf-token` wie die Vue-Komponente, dann Multipart-`POST /anfrage`):
- E1 SMTP kaputt (`MAIL_MAILER=smtp`, `MAIL_HOST=127.0.0.1`, `MAIL_PORT=1`), mit PDF-Anhang
- E2 `MAIL_MAILER=log`, `KONTAKTFORMULAR_MAIL_BCC="das ist keine adresse"`
- E3 `MAIL_MAILER=log`, `KONTAKTFORMULAR_MAIL_BCC=lead@example.test`

| AK / Aussage (Zitat) | Ergebnis | Nachweis |
|---|---|---|
| AK1 „Gegeben ein Mailer, der beim Versand der Anfrage-Mail eine Ausnahme wirft (`TransportException`) — Wenn ein Besucher ein gültiges Formular absendet — Dann leitet die Antwort auf die Route `danke_formular` weiter“ | bestanden | `test_transportfehler_leitet_auf_danke_formular_weiter` grün (Transport wirft beim echten `Mail::to()->send()`); E1: `302 → /danke-formular` |
| AK1 „(kein 500)“ | bestanden | `assertRedirect` prüft 3xx; E1 Status 302, `server.log` ohne Fehler |
| AK2 „Dann ist die Anfrage genau einmal in der Tabelle gespeichert“ | bestanden | `test_anfrage_ist_trotz_versandfehler_genau_einmal_mit_formularwerten_gespeichert` (`assertDatabaseCount 1`); E1: genau eine Zeile id 1 |
| AK2 „mit den Formularwerten“ | bestanden | derselbe Test prüft name, email, telefon, nachricht, url; E1: Zeile mit „Max Echt“, max@example.test, 0123, Nachricht, url, file_name „Bericht“ |
| AK2 „ein Anhang liegt genau einmal in der Ablage“ | bestanden | `test_anhang_liegt_trotz_versandfehler_genau_einmal_in_der_ablage` (`allFiles()` = genau `kontaktformular/<hash>.zip`); E1: genau eine neue Datei `storage/app/kontaktformular/BAQvR410….zip` (= file_hash), kein Rest-PDF |
| AK3 „Dann wird die Ausnahme über Laravels Fehlerbehandlung gemeldet (`report()`“ | bestanden | Code: `report(AnfrageMailNichtVersendet::fuer($kontakt, $e))`; E1: genau ein Eintrag `testing.ERROR` in `laravel.log`, Ursache als `[previous exception] TransportException` |
| AK3 „sichtbar im Log mit der Meldung der Ausnahme)“ | bestanden | `test_ausnahme_wird_mit_ihrer_meldung_im_log_gemeldet` grün; E1: „Anfrage-Mail nicht versendet: Connection could not be established with host "127.0.0.1:1" …“ |
| AK3 „und der Log-Eintrag nennt die id bzw. `nummer` der gespeicherten Anfrage“ | bestanden | `test_log_eintrag_nennt_id_oder_nummer_der_gespeicherten_anfrage` (id 62 ≠ nummer 3, Kontext geprüft); E1: Kontext `{"id":1,"nummer":1}`, E2: `{"id":2,"nummer":2}` |
| AK4 „Gegeben ein Mailer, der beim Versand eine andere Ausnahme wirft (z. B. `RfcComplianceException` bei ungültiger `mailBcc`-Adresse) — Dann gelten AK1–AK3 ebenso“ (AK1) | bestanden | `test_andere_ausnahme_leitet_auf_danke_formular_weiter` (RfcComplianceException, RuntimeException) grün; E2: `302 → /danke-formular` |
| AK4 (AK2) | bestanden | `test_andere_ausnahme_laesst_anfrage_und_anhang_genau_einmal_gespeichert` (beide Datensätze) grün; E2: genau eine neue Zeile id 2 „Bcc Kaputt“ |
| AK4 (AK3) | bestanden | `test_andere_ausnahme_wird_mit_meldung_und_id_oder_nummer_gemeldet` grün; `test_ungueltige_bcc_adresse_…` mit echter ungültiger BCC grün; E2: ERROR „Anfrage-Mail nicht versendet: Email "das ist keine adresse" does not comply with addr-spec of RFC 2822.“ mit `{"id":2,"nummer":2}` |
| AK5 „Gegeben ein funktionierender Mailer — Dann wird die Anfrage-Mail wie bisher genau einmal an `mailTo` … versendet“ | bestanden | `test_ohne_fehler_wird_die_mail_genau_einmal_an_mail_to_mit_bcc_versendet` (`Mail::sent` = 1, `hasTo`); E3: genau eine Mail im Log (1× `Message-ID`), `To: kontakt@it-hilbert.com` (Host-Config `mailTo`), Betreff „Anfrage von XXX [3]“ |
| AK5 „(mit BCC laut `mailBcc`)“ | bestanden | derselbe Test `hasBcc('lead@example.com')`; bestehender `AnfrageBccTest` in der Suite grün. Im Log-Mailer (E3) ist BCC nicht sichtbar (Symfony entfernt den Header), daher nur per Test belegt |
| AK5 „die Antwort leitet auf `danke_formular` weiter“ | bestanden | Tests `assertRedirect(route('danke_formular'))`; E3: `302 → /danke-formular` |
| AK5 „und es wird nichts als Fehler gemeldet“ | bestanden | `test_ohne_fehler_wird_nichts_als_fehler_gemeldet` (keine Einträge ≥ ERROR, mit Gegenprobe Versand = 1); E3: 0 ERROR-Zeilen im `laravel.log` seit dem Request |
| AK6 „Die README nennt im Abschnitt „Konfiguration“ (bei `mailTo`/`mailBcc`), dass ein Versandfehler den Besucher nicht aufhält“ | bestanden | README Z. 38, unter `## Konfiguration` direkt nach dem Config-Block mit `mailTo`/`mailBcc`, vor `### Kopie an das Lead-Postfach`: „hält das den Besucher nicht auf: Er sieht die Danke-Seite“ |
| AK6 „gemeldet wird (Log)“ | bestanden | ebd.: „der Fehler wird gemeldet (Log, mit id und Nummer der Anfrage)“ |
| AK6 „und die Anfrage gespeichert bleibt“ | bestanden | ebd.: „und die Anfrage bleibt gespeichert“ |
| Randfall 1 „Fehler vor dem Speichern (Validierung, Bot-Erkennung) → Verhalten unverändert.“ (Validierung) | bestanden | `test_validierungsfehler_bleibt_unveraendert` (Gegenprobe gültig → Danke; ungültige Email → zurück mit Fehler `Email`, keine neue Zeile); Code: Validierung unverändert vor `save()` |
| Randfall 1 (Bot-Erkennung) | bestanden | `test_bot_erkennung_bleibt_unveraendert` (`fax` gefüllt → `danke_bot_formular`, keine neue Zeile); Code unverändert |
| Randfall 2 „Fehler beim Speichern selbst (DB) → nicht abgefangen“ | bestanden | Code: `$kontakt->save()` steht vor dem `try`, nur der Versand ist umschlossen; `test_fehler_beim_speichern_wird_nicht_abgefangen` grün |
| Randfall 2 „Verhalten unverändert (500), denn dann ist nichts gespeichert“ | bestanden | derselbe Test: nach `Schema::drop` → 500, keine weitere Mail (`Mail::sent` bleibt 1) |
| Nicht-Ziel „Versand per Queue (`ShouldQueue`)“ | eingehalten | `Mail::to()->send()` synchron, `Anfrage` ohne `ShouldQueue` |
| Nicht-Ziel „Erneuter Versandversuch, Hinweis an den Betreiber außerhalb des Logs, Admin-Ansicht „nicht versendet““ | eingehalten | nur `report()`, keine Retry-Logik, keine neue Ansicht/Spalte |
| Nicht-Ziel „Validierung der BCC-Adressen“ | eingehalten | keine Validierung ergänzt; ungültige BCC landet im `catch` |
| Nicht-Ziel „Release/Tag“ | eingehalten | kein Tag auf dem Branch |
| Entscheidungsspielraum „Keine neuen Pakete, keine Migration“ | eingehalten | `composer.json` und Migrationen unverändert |

## Sicherheit
- Log-Kontext der Ausnahme nur `id` und `nummer` (`AnfrageMailNichtVersendet::context()`), keine Formulardaten des Besuchers. Die Meldung übernimmt die Meldung der Ursache (SMTP-Antwort bzw. die ungültige BCC-Adresse aus der Config) — keine Besucherdaten in E1/E2.
- `catch (Throwable)` umschließt nur Mailable-Aufbau und Versand; Speichern, Validierung, Bot-Erkennung liegen außerhalb. Keine neue Route, keine Eingabe, kein Mass-Assignment, keine Ausgabe geändert.
- Pint auf den beiden geänderten PHP-Dateien: bestanden.

## Regressionen
- Volle Suite im Platz `php artisan test --parallel --compact`: OK (2660 Tests, 24925 Assertions), u. a. `KontaktformularTest`, `AnfrageBccTest`, `AnhaengeDiskTest`.
- Feature-Tests `MailversandFehlerTest.php`: 17 passed (73 Assertions).
- `laravel.log` der Echtläufe: je Versandfehler genau ein ERROR, beim funktionierenden Versand keiner; `server.log` ohne Fehler.
- Hinweis (kein Fund): PHPStan meldet auf dem Controller `property.notFound` für die Model-Felder (`$name`, `$nummer` …), auch an der neuen Stelle `$this->anfrage->id/nummer`. Ursache ist vorbestehend (Model ohne `@property`, Paket nicht im PHPStan-Bereich `app/`, `module/`), nicht durch das Feature entstanden.

## Neue Tasks: keine

## Fazit: freigegeben
