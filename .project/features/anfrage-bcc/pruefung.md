# Prüfung: anfrage-bcc — 2026-10-26

Branch `feature/anfrage-bcc` gegen `main` (3 Commits: Spec/Plan, rote Tests, Umsetzung). Geändert: `src/Mail/Anfrage.php`, `src/Config/config.php`, `README.md`, neu `tests/Feature/AnfrageBccTest.php`.

Eigene Nachweise neben der Paket-Suite: ein Wegwerf-Test im Scratchpad (nicht im Repo), der eine Anfrage per `POST route('anfrage')` über den **echten Array-Transport** verschickt (kein `Mail::fake()`) und die versendete Symfony-Mail ausliest: Envelope-Empfänger, `To`, `Bcc`, `From`, `Reply-To`, Betreff, HTML- und Text-Teil. Dieser Lauf wird unten „Echtversand“ genannt.

| AK / Aussage (Zitat) | Ergebnis | Nachweis |
|---|---|---|
| AK1 „Gegeben die Config `kontaktformular.mailBcc` (env `KONTAKTFORMULAR_MAIL_BCC`)“ | bestanden | `src/Config/config.php`: `'mailBcc' => env('KONTAKTFORMULAR_MAIL_BCC', '')`; Config-Datei mit `putenv('KONTAKTFORMULAR_MAIL_BCC=marketing@…, b@y.de')` geladen → Wert kommt an, ohne env → `''` |
| AK1 „mit einer oder mehreren Adressen … Dann hat die Mail `Anfrage` jede dieser Adressen als BCC-Empfänger“ | bestanden | `test_einzelne_adresse_wird_bcc_empfaenger`, `test_mehrere_adressen_im_array_sind_alle_bcc` grün; Echtversand mit `' a@x.de , b@y.de ,'` und mit `['a@x.de','b@y.de']`: Header `Bcc` = a@x.de, b@y.de; Envelope = to@example.com, a@x.de, b@y.de; die rohe Mail enthält keinen `Bcc:`-Header (Adressen bleiben verborgen) |
| AK1 „und der Empfänger `mailTo` bleibt unverändert“ | bestanden | `test_mail_to_bleibt_bei_gesetztem_bcc_unveraendert` (genau 1 To, BCC-Adresse nicht in To) grün; Echtversand: `To` = to@example.com mit und ohne BCC; Controller unverändert (`Mail::to(config('kontaktformular.mailTo'))`) |
| AK2 „Gegeben `mailBcc` ist nicht gesetzt … Dann hat die Mail keinen BCC-Empfänger“ | bestanden | `test_nicht_gesetzter_wert_ergibt_kein_bcc` (Schlüssel entfernt, mit Gegenprobe) grün; Echtversand mit `null`: `Bcc` leer, Envelope nur to@example.com; ohne env liefert die Config `''` |
| AK2 „Gegeben `mailBcc` ist … leer … Dann hat die Mail keinen BCC-Empfänger“ | bestanden | `test_leerer_string_ergibt_kein_bcc`, `test_null_und_leeres_array_ergeben_kein_bcc`, `test_nur_leerzeichen_und_kommas_ergeben_kein_bcc` grün (je mit Gegenprobe); Echtversand mit `''`: `Bcc` leer, Envelope nur to@example.com |
| AK3 „Dann sind Absender (`mailFrom`) … gleich wie ohne BCC“ | bestanden | `test_absender_betreff_und_reply_to_sind_mit_bcc_gleich` grün; Echtversand: `From` = from@example.com in allen vier Läufen (`''`, `null`, String, Array) |
| AK3 „Betreff … gleich wie ohne BCC“ | bestanden | derselbe Test; Echtversand: Betreff `Test-Anfrage [1]` in allen vier Läufen |
| AK3 „Reply-To … gleich wie ohne BCC“ | bestanden | derselbe Test; Echtversand: `Reply-To` = max@example.com in allen vier Läufen |
| AK3 „der Inhalt von HTML- und Text-Teil gleich wie ohne BCC“ | bestanden | `test_html_und_text_inhalt_sind_mit_bcc_gleich` grün (rendert den Text-Teil allerdings über `view(...)`, nicht aus der versendeten Mail); deshalb Echtversand: md5 über HTML-Teil, Text-Teil, From, Reply-To und Betreff in allen vier Läufen identisch (`400eeb60…`). Views im Diff unverändert, `build()` setzt dieselbe Kette wie auf `main` |
| AK3 „insbesondere bleiben die Zeilen `Nummer:`, `Datum:` und `Site:` im Text-Teil unverändert“ | bestanden | `test_zeilen_nummer_datum_und_site_im_text_teil_bleiben_unveraendert` grün; Echtversand, Text-Teil mit BCC: `Nummer: 1`, `Datum: 2026-10-26 12:00:00`, `Site: http://localhost`, gleich wie ohne BCC |
| AK4 „Die README nennt den Schlüssel `mailBcc` / `KONTAKTFORMULAR_MAIL_BCC`“ | bestanden | README: Config-Beispiel `'mailBcc' => env('KONTAKTFORMULAR_MAIL_BCC', '')` und Abschnitt „Kopie an das Lead-Postfach (`mailBcc`)“ |
| AK4 „das Format für mehrere Adressen“ | bestanden | README: „Mehrere Adressen: durch Komma getrennt (`a@x.de, b@y.de`) …; in der veröffentlichten Config ist auch ein Array möglich“ |
| AK4 „und den Zweck (Lead-Postfach)“ | bestanden | README: „schickt jede Anfrage zusätzlich als BCC-Kopie an ein Lead-Postfach, z. B. `marketing@it-entwickler-hilbert.de` für die Marketing-Auswertung“ |
| Randfall 1 „Wert als Array in der veröffentlichten Config (`['a@x.de', 'b@y.de']`) → alle Adressen sind BCC.“ | bestanden | `test_array_aus_der_veroeffentlichten_config_ergibt_alle_adressen_als_bcc` grün; Echtversand mit Array: `Bcc` = a@x.de, b@y.de |
| Randfall 2 „Wert als String mit mehreren Adressen, durch Komma getrennt (`a@x.de, b@y.de`) → beide Adressen sind BCC“ | bestanden | `test_komma_getrennter_string_ergibt_beide_adressen_als_bcc` grün; Echtversand bestätigt |
| Randfall 2 „Leerzeichen um die Adressen werden ignoriert“ | bestanden | `test_leerzeichen_um_die_adressen_werden_ignoriert` (`'  a@x.de  ,   b@y.de '`) grün; Echtversand mit `' a@x.de , b@y.de ,'` → Adressen ohne Leerzeichen |
| Randfall 2 „leere Teile (`a@x.de,`) ergeben keinen Empfänger“ | bestanden | `test_leerer_teil_nach_komma_ergibt_keinen_empfaenger` (genau 1 BCC), `test_leere_teile_in_der_mitte_und_nur_leerzeichen_ergeben_keinen_empfaenger` grün; Echtversand mit Komma am Ende: genau 2 BCC, Envelope ohne leeren Empfänger |
| Randfall 3 „Bot-Anfragen (Honeypot …) → wie bisher keine Mail, also auch keine Kopie.“ | bestanden | `test_honeypot_erzeugt_keine_mail_und_keine_kopie` (mit Gegenprobe) grün; Echtversand mit `fax` und gesetztem BCC: 0 Nachrichten im Transport, Redirect `danke-bot-formular` |
| Randfall 3 „Bot-Anfragen (… zu kurze Nachricht) → wie bisher keine Mail, also auch keine Kopie.“ | bestanden | `test_zu_kurze_nachricht_erzeugt_keine_mail_und_keine_kopie` (mit Gegenprobe) grün; Echtversand mit `Nachricht = 'kurz'`: 0 Nachrichten, Redirect `danke-bot-formular` |
| Nicht-Ziel „Kontaktformular 7 auf den WordPress-Seiten“ | eingehalten | Diff betrifft nur das Paket |
| Nicht-Ziel „Rollout auf die Sites (`.env` setzen, Test-Anfrage, DKIM prüfen)“ | eingehalten | keine `.env` geändert, Host-`.env` ohne `KONTAKTFORMULAR_MAIL_BCC`; DKIM hier nicht prüfbar (Rollout) |
| Nicht-Ziel „Release/Tag“ | eingehalten | `git tag --contains 6c177b6` leer |
| Nicht-Ziel „Prüfen der Adressen auf Gültigkeit beim Booten“ | eingehalten | keine Prüfung beim Booten gebaut (Folge siehe Hinweis 1) |

## Sicherheit
- **Herkunft der BCC-Adressen:** nur aus der Config, kein Request-Wert fließt in `bcc()`, also keine Header-Injection durch Besucher. `Reply-To` bleibt die validierte `Email` (`required|email`), unverändert.
- **Sichtbarkeit:** Im Echtversand enthält die rohe Mail keinen `Bcc:`-Header. Die BCC-Adressen erscheinen nur im SMTP-Envelope und sind für `mailTo` nicht sichtbar.
- **Mass-Assignment / Autorisierung / Mandanten:** nicht berührt (Controller und Model unverändert).
- **Ausgabe-Escaping:** Views unverändert. HTML-Eingaben im Namen werden im HTML-Teil maskiert (`Max &lt;b&gt;…`).

## Regressionen
- Paket-Suite (`vendor/bin/phpunit` im Paket): **55 Tests, 248 Assertions, grün**. Davon 17 neue in `AnfrageBccTest`. Bestehende Testdateien sind im Diff unverändert.
- Host-Suite (`php artisan test --parallel --compact`, bindet `packages/kontaktformular/tests/Feature` ein): **612 Tests, 3279 Assertions, grün**.
- Pint (`vendor/bin/pint --test` auf `Anfrage.php`, `config.php`): bestanden. Die Umformatierung bestehender Zeilen in `config.php` betrifft nur Leerzeichen.
- `storage/logs/laravel.log` (Host): letzte Änderung 2026-10-23, keine neuen Einträge durch diese Prüfung.
- Browser-Logs: nicht anwendbar, das Feature ändert kein Frontend.
- Ohne `mailBcc` ist das Verhalten gleich wie auf `main`: kein BCC, Envelope nur `mailTo`, Inhalte byte-gleich mit den BCC-Läufen.

## Hinweise (keine AK-Verstöße)
1. **Tippfehler in `mailBcc` legt auch die Hauptmail lahm.** Echtversand mit `mailBcc = 'kein-adresse'`: `RfcComplianceException` beim Versand. Die Anfrage ist schon gespeichert (1 DB-Zeile), aber **auch `mailTo` bekommt keine Mail**, und der Besucher sieht einen Fehler 500. Die Spec nimmt das per Nicht-Ziel in Kauf („eine falsche Adresse meldet der Mailer“). Beim Rollout sollte das aber bekannt sein: Eine falsche Lead-Adresse kostet echte Anfragen. Abhilfe wäre eine eigene Folge-Spec, z. B. ungültige BCC-Adressen verwerfen und loggen.
2. **Testlücke (gedeckt durch den Echtversand):** `AnfrageBccTest` prüft unter `Mail::fake()` nur die Eigenschaften des Mailables und rendert den Text-Teil über `view()`. Ob die Adressen wirklich im Envelope der versendeten Mail landen, prüft kein Repo-Test. Hier ist es über den Array-Transport nachgewiesen.
3. Schon vor diesem Feature vorhanden und unverändert: Der Text-Teil maskiert HTML (`Name: Max &lt;b&gt;…`), weil `anfrage_plain.blade.php` `{{ }}` nutzt. Das berührt kein AK dieses Features. Erwähnt, weil das Lead-Skript diesen Text parst.
4. DKIM (`dkim=pass` für das Lead-Skript) ist nicht prüfbar. Das ist laut Spec Teil des Rollouts.

## Fazit: freigegeben
