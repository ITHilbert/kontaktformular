# Prüfung: anfrage-website-feld — 2026-11-16

Geprüft: Paket `kontaktformular`, Diff `2.2.1..94ecead` (Host-Merge b8880b9 setzt nur den Submodul-Zeiger). Suite der Pakettests einmal gelaufen: 721 Tests grün.

| AK / Aussage (Zitat) | Ergebnis | Nachweis |
|---|---|---|
| AK1 „es gibt `feld_website` (bool, Standard `false`, env `KONTAKTFORMULAR_FELD_WEBSITE`)“ | bestanden | `src/Config/config.php` per `filter_var(..., FILTER_VALIDATE_BOOL)`; `test_config_standards_und_auswertung_der_umgebungsvariablen` grün (lädt die Config-Datei mit gesetzter env neu) |
| AK1 „und `nachricht_pflicht` (bool, Standard `true`, env `KONTAKTFORMULAR_NACHRICHT_PFLICHT`)“ | bestanden | wie oben |
| AK1 „beide folgen der Auswertung von `herkunft_erfassen` (aus bei `false`, `0`, `off`, `no` oder leerem Wert, sonst an)“ | bestanden | Test prüft je Schlüssel `false/0/off/no/''` → false und `true/1/on/yes` → true |
| AK2 „Dann steht der Wert in der neuen, nullbaren Spalte `website`“ | bestanden | `test_website_wird_in_spalte_website_gespeichert` grün (`https://example.org/shop` roh aus der DB gelesen) |
| AK2 „(Migration im Paket, `string` mit 2048 Zeichen)“ | bestanden | Migration `2026_11_16_120000_add_website_to_kontaktformular_table.php`: `string('website', 2048)->nullable()`, Laden über `loadMigrationsFrom`. Eigene Sonde (Capsule, SQLite): alle Paketmigrationen `up` → Spalte nullable; `down` → Spalte weg, Bestandszeile bleibt |
| AK3 „Wenn `Website` fehlt oder leer ist, wird die Anfrage normal gespeichert und `website` bleibt `null`“ | bestanden | `test_website_fehlt_oder_ist_leer_wird_normal_gespeichert_mit_null` grün (mit Gegenprobe) |
| AK3 „ist `Website` länger als 2048 Zeichen, lehnt die Validierung die Anfrage ab (Rückkehr zum Formular mit Fehler, kein Datensatz, keine Mail)“ | bestanden | `test_website_ueber_2048_zeichen_wird_abgelehnt`: 2048 angenommen, 2049 → Session-Fehler `Website`, Zeilen- und Mailzahl unverändert |
| AK3 „Ein Wert ohne Schema (`example.org`) wird angenommen.“ | bestanden | derselbe AK3-Test, `example.org` gespeichert; Regel `nullable\|string\|max:2048` ohne `url` |
| AK4 „enthalten HTML- und Text-Teil direkt nach der Zeile `E-Mail:` bzw. `Telefon:` (also vor `Mitteilung:`) eine Zeile `Website:` mit dem Wert“ | bestanden | `test_mail_zeigt_website_direkt_nach_telefon_…` (vollständige Zeilenfolge im Text, Label-Folge im HTML) und `test_website_steht_ohne_telefon_direkt_nach_der_email_…` |
| AK4 „HTML-Zeichen sind maskiert wie bei den anderen Feldern“ | bestanden | `{{ }}` in beiden Views wie bei Name/Telefon; Test mit `x<script>…&"y`: kein `<script>` im HTML |
| AK4 „Hat die Anfrage keine `website`, steht keine Zeile `Website:` in der Mail.“ | bestanden | `test_mail_ohne_website_hat_keine_zeile_website` (Text und HTML, mit Gegenprobe) |
| AK5 „werden sie beim Speichern durch je ein Leerzeichen ersetzt“ | bestanden | Controller `str_replace(["\r", "\n"], ' ', …)`; Test: `"a\nb\rc"` → `a b c`, `\r\n` → zwei Leerzeichen |
| AK5 „sodass im Text-Teil der Mail keine zusätzliche Zeile (z. B. eine eingeschleuste Zeile `Site: …`) entstehen kann“ | bestanden | Test mit `example.org\r\nSite: http://evil.example`: genau eine Zeile `Site: http://localhost` |
| AK6 „Dann wird der Wert weder gespeichert …“ | bestanden | `test_ohne_feld_website_wird_der_wert_auch_mit_vorhandener_spalte_nicht_gespeichert` (Spalte vorhanden, Wert `null`); Regel `Website` wird bei `false` gar nicht validiert/übernommen |
| AK6 „… noch in der Mail ausgegeben“ | bestanden | beide AK6-Tests: keine Zeile `Website:` in Text und HTML |
| AK6 „und die Anfrage funktioniert auch auf einer Tabelle ohne die Spalte `website`“ | bestanden | `test_ohne_feld_website_funktioniert_die_anfrage_auf_tabelle_ohne_spalte`: Schlüssel nicht gesetzt und `false`, Tabelle ohne Spalte → Danke-Seite, Datensatz, Mail. Gegenprobe im selben Test. Mail-Views nutzen `!empty($kontakt->website)`, das wirft auch im Strict Mode nicht |
| AK7 „Dann verhält sie sich wie bisher: Weiterleitung zur Bot-Danke-Seite, kein Datensatz, keine Mail“ | bestanden | `test_nachricht_pflicht_standard_leitet_ohne_nachricht_zur_bot_seite`: fehlend, `''`, `'   '` bei nicht gesetztem und bei explizit `true`, mit Gegenprobe |
| AK8 „Dann wird sie gespeichert (`nachricht` = leerer String), die Mail verschickt und zur Danke-Seite (`danke_formular`) weitergeleitet“ | bestanden | `test_ohne_nachricht_wird_gespeichert_verschickt_…`: fehlend, `''`, `'   '` → Redirect `danke_formular`, Zeile +1, Mail +1, `nachricht === ''` |
| AK8 „In der Mail steht dann keine Zeile `Mitteilung:`“ | bestanden | derselbe Test (Text und HTML) |
| AK8 „mit Nachricht bleibt sie wie bisher“ | bestanden | derselbe Test, Teil „Mit Nachricht bleibt die Zeile“ |
| AK9 „Wenn die `Nachricht` gefüllt, aber kürzer als 10 Zeichen ist oder kein Leerzeichen enthält — Dann gilt die bisherige Bot-Heuristik (Weiterleitung zur Bot-Danke-Seite, kein Datensatz, keine Mail)“ | bestanden | Bedingung `($nachrichtPflicht \|\| $nachricht !== '') && (…)` gelesen; `test_gefuellte_zu_kurze_oder_leerzeichenlose_nachricht_bleibt_bot_auch_ohne_pflicht` grün (`zu kurz`, `ohneleerzeichenundlang`, Gegenproben leer und gültig) |
| AK9 „Der Honeypot `fax` greift in beiden Einstellungen wie bisher.“ | bestanden | `test_honeypot_fax_greift_mit_und_ohne_pflicht` (auch `fax` + leere Nachricht bei `false`) |
| AK10 „Props `feld-website` (Boolean, Standard `false`) und `nachricht-pflicht` (Boolean, Standard `true`)“ | bestanden | eigene Sonde: SFC mit `vue-template-compiler` aus dem Platz kompiliert, `vm._render()` (Vue 2.7.16); `props.*.type === Boolean`, ohne Props `feldWebsite=false`, `nachrichtPflicht=true` |
| AK10 „Mit `feld-website` zeigt sie nach der Telefonnummer ein optionales Feld `Website` (Label „Adresse Ihrer Website“, `type="text"` mit `inputmode="url"`, `maxlength="2048"`, kein `required`)“ | bestanden | Sonde `feldWebsite=true` und „beide“: Feldfolge `…,Telefon,Website,Nachricht,…`, attrs `type=text, inputmode=url, maxlength=2048`, kein `required`, Label „Adresse Ihrer Website“ |
| AK10 „ohne Prop erscheint kein solches Feld“ | bestanden | Sonde „ohne Props“ und `nachrichtPflicht=false`: kein Input `Website`, kein Label `contact_website` |
| AK10 „Mit `nachricht-pflicht=false` hat das Nachricht-Feld kein `required`, das Label „Ihre Nachricht“ ohne `*` und mit dem Zusatz „(optional)““ | bestanden | Sonde: Textarea `required:false` (Vue 2 entfernt falsche Boolean-Attribute, `isBooleanAttr('required')` → `removeAttribute`), Label „Ihre Nachricht (optional)“ |
| AK10 „ohne die Einstellung bleibt es wie heute“ | bestanden | Sonde ohne Props: `required:true`, Label „Ihre Nachricht *“ (wie 2.2.1) |
| AK11 „übergibt sie `feld-website` und `nachricht-pflicht` entsprechend der Config an die Komponente“ | bestanden | `show.blade.php` mit `:feld-website="true\|false"` und `:nachricht-pflicht="…"` (Vue-Bindung, also Boolean); `test_show_view_uebergibt_die_config_an_die_komponente` grün für beide Kombinationen |
| AK12 „Die README nennt beide Schlüssel samt env-Namen, Standards und die Props der Komponente“ | bestanden | README Abschnitt „Optionale Felder: Website und Nachricht“ und Config-Block |
| AK12 „beschreibt die neue Spalte `website` (Migration ausführen, bevor `feld_website` an ist)“ | bestanden | „**Vorher `php artisan migrate` ausführen**“ |
| AK12 „das Verhalten der Bot-Heuristik bei `nachricht_pflicht=false` (AK9)“ | bestanden | „Eine ausgefüllte Nachricht geht weiter durch die Bot-Prüfung … das Honeypot-Feld `fax` greift immer“ |
| AK12 „den Hinweis „Hosts mit eigener Kopie der Komponente bauen das Feld selbst ein““ | nicht bestanden | README nennt nur „Wer die Komponente selbst einbindet (eigene Kopie der View), setzt diese Props selbst“ und „Hosts, die die Komponente in ihr Bundle bauen, müssen neu bauen“. Dass Hosts mit eigener Kopie der **Komponente** (`.vue`) das Feld selbst einbauen müssen, fehlt. Der Test prüft nur das Teilwort `eigene Kopie` und wird schon durch „eigene Kopie der View“ grün |
| AK12 „und den Hinweis, dass die Datenschutzerklärung des Hosts das neue Feld nennen muss“ | bestanden | „Datenschutz: … die Datenschutzerklärung des Projekts bei Aktivierung prüfen und ergänzen“ |
| Randfall 1 „`Website` mit nur Leerzeichen → gilt als leer (`website` bleibt `null`)“ | bestanden | `test_website_nur_aus_leerzeichen_gilt_als_leer` (mit Gegenprobe) |
| Randfall 2 „`nachricht_pflicht=false` und `Nachricht` besteht nur aus Leerzeichen → gilt als leer (AK8)“ | bestanden | `test_nachricht_nur_aus_leerzeichen_gilt_je_nach_einstellung_als_leer` und AK8-Test mit `'   '` |
| Randfall 3 „`feld_website=true` ohne ausgeführte Migration → Datenbankfehler beim Speichern“ | bestanden | `test_feld_website_an_ohne_migration_wirft_datenbankfehler_beim_speichern`: QueryException, kein Datensatz, keine Mail |
| Randfall 3 „die README nennt die Reihenfolge (AK12), kein Abfangen im Code“ | bestanden | README „Vorher `php artisan migrate` ausführen“; im Controller kein try/catch um `save()` |
| Randfall 4 „Bestehende Datensätze → `website` bleibt `null`, kein Nachbefüllen“ | bestanden | `test_bestehende_datensaetze_behalten_website_null_ohne_nachbefuellen`; Migration ohne Update. Sonde: Bestandszeile übersteht auch `down` |
| Randfall 5 „Mail-Betreff, Absender, Reply-To und die Zeilen `Nummer:`, `Datum:`, `Site:` im Text-Teil bleiben unverändert“ | bestanden | AK4-Test prüft die Zeilenfolge Nummer…Site wörtlich und `hasFrom`, `hasReplyTo`, `hasSubject('Test-Anfrage [1]')`; `Mail/Anfrage.php` ohne Änderung |
| Nicht-Ziel „Format-Prüfung der Website-Adresse“ | eingehalten | keine `url`-Regel |
| Nicht-Ziel „eigene Kopien in Hosts, Datenschutzerklärung, Release/Tag“ | eingehalten | Diff nur im Paket, kein Tag |
| Risiko „Risiko: normal“ | nicht bestanden | Das Feature erhebt, speichert und versendet eine zusätzliche personenbezogene Angabe der Anfragenden (Website-Adresse). Das ist der Bereich Kundendaten (`/neues-feature`, Schritt 1.4, #1204); die Spec selbst verlangt dafür den Datenschutz-Hinweis (AK12) |

## Sicherheit
- **Ausgabe-Escaping:** `website` wird in beiden Mail-Views per `{{ }}` ausgegeben, an keiner anderen Stelle (grep `src/`: nur Mail-Views, keine `{!! !!}`). Test mit `<script>` belegt das für HTML.
- **Header-/Zeileninjektion:** `website` landet nur im Body, nicht in Headern; CR/LF werden ersetzt (AK5). Reply-To bleibt `email` (validiert `email`).
- **Mass-Assignment:** `website` steht in `$fillable`, der Controller setzt aber jedes Feld einzeln aus `$validated`; ohne `feld_website` wird `Website` nicht übernommen.
- **Validierung:** `nullable|string|max:2048` verhindert Arrays und Überlänge vor dem `trim`-Cast der Website.
- Mandantentrennung/Autorisierung: nicht berührt (öffentliches Formular, keine neuen Routen).
- Kritisch-Fehlerpfad, selbst geprüft (falls die Spec auf `kritisch` geht): Bei Mailfehler bleibt `website` gespeichert und wird nicht geloggt (`AnfrageMailNichtVersendet::context()` nur `id`, `nummer`). Bei Validierungsfehler eines anderen Felds entsteht kein Datensatz, weil `validate()` vor `save()` läuft.

## Regressionen
- Paket-Suite im Platz (`cd …/reviewer/packages/kontaktformular && …/vendor/bin/phpunit -c phpunit.xml.dist --bootstrap …/vendor/autoload.php`): **OK (721 tests, 9366 assertions)**, ein Lauf, 14 s.
- Geänderter Bestandstest `AnfrageHerkunftTest` (Randfall 8): rollt jetzt gezielt per `--path` nur die Herkunft-Migration zurück statt `--step 1`. Fachlich richtig, weil die neue Migration sonst zuerst zurückgerollt würde; die Aussage des Tests bleibt gleich.
- Verhalten bei Standard-Config: Die Nachricht wird jetzt **getrimmt** gespeichert (vorher `$validated['Nachricht']` roh). Das ist eine kleine Änderung für alle Hosts, siehe sollte-Fund.

## Funde: 2 muss, 1 sollte, 0 kann
- muss: AK12 nicht erfüllt. Der Hinweis „Hosts mit eigener Kopie der Komponente bauen das Feld selbst ein“ fehlt in der README. Sie spricht nur von einer eigenen Kopie der **View** (Props setzen) und vom Neubauen des Bundles. Der AK12-Test prüft nur das Teilwort `eigene Kopie` und ist deshalb grün.
- muss: Risiko kritisch nötig. Die Spec steht auf `Risiko: normal`, das Feature erhebt und speichert aber neue Kundendaten (Website der Anfragenden, Spalte `website`, Mail an `mailTo`/`mailBcc`). Der Moderator setzt die Spec auf `kritisch` oder lässt die Einstufung vom User bestätigen. Die zusätzliche Fehlerpfad-Prüfung für „kritisch“ habe ich oben unter Sicherheit schon gemacht, ohne Befund.
- sollte: Die Nachricht wird seit dem Diff auch bei Standard-Config getrimmt gespeichert. Die Spec verlangt das nicht („Standards lassen alle bestehenden Hosts unverändert“). (→ Folge-Task #1325)

## Neue Tasks: #1325 Anfrage – Nacharbeit aus Review anfrage-website-feld, #1327 KontaktformularController::anfrage – Nachricht als Liste (Nachricht[]) ergibt 500 statt Bot-Seite bzw. Validierungsfehler (Altlast außerhalb der Spec)

## Fehlerprotokoll
- {"schwere":"muss","ak":"AK12","fund":"README-Hinweis 'Hosts mit eigener Kopie der Komponente bauen das Feld selbst ein' fehlt; es steht nur 'eigene Kopie der View'","ursache":"README-Test prüft nur das Teilwort 'eigene Kopie' statt der Aussage; jeder Satz mit diesem Teilwort macht ihn grün","anweisung":"Doku-AKs je Hinweis mit einem Wortlaut prüfen, der nur auf genau diese Aussage passt (Objekt mitprüfen: 'Kopie der Komponente' statt 'eigene Kopie'), und jeden Spec-Hinweis einzeln in der README abhaken"}
- {"schwere":"muss","ak":"","fund":"Spec auf Risiko normal, obwohl das Feature eine neue personenbezogene Angabe der Anfragenden erhebt, speichert und versendet","ursache":"Risiko-Einstufung nur nach technischem Umfang (eine Spalte, ein Feld) statt nach den fünf Bereichen; Kundendaten übersehen, obwohl die Spec selbst einen Datenschutz-Hinweis verlangt","anweisung":"Verlangt eine Spec einen Datenschutz-Hinweis oder legt sie ein neues Feld mit personenbezogenen Daten an, Risiko auf kritisch setzen oder die Einstufung ausdrücklich vom User bestätigen lassen"}
- {"schwere":"sollte","ak":"AK7","fund":"Nachricht wird auch bei Standard-Config getrimmt gespeichert (vorher roh)","ursache":"Beim Umbau der Nachrichten-Prüfung wurde der getrimmte Hilfswert auch zum Speichern genutzt; kein Test hält den gespeicherten Wert bei gefüllter Nachricht fest","anweisung":"Bei Änderungen unter 'Standard wie bisher' den gespeicherten Wert des Bestandsfalls unverändert lassen oder die Änderung als Spec-Lücke melden"}

## Rechtshinweise
- README `packages/kontaktformular/README.md`, Abschnitt „Optionale Felder“: Die Website-Adresse ist eine neue personenbezogene Angabe. Die Datenschutzerklärung jedes Hosts, der `feld_website` einschaltet, muss sie nennen.
- `src/Resources/Vue/kontaktformular.vue`: Der Einwilligungstext verlinkt fest auf `https://www.it-hilbert.com/datenschutz`. Das gilt für alle Hosts, ist eine Altlast und nicht Teil dieses Features.

## Fazit: nicht freigegeben
Zwei muss-Funde: AK12 ist bei einem Hinweis nicht erfüllt, und die Spec braucht die Risiko-Einstufung `kritisch` (Kundendaten). Alle anderen Aussagen (AK1–AK11, Randfälle 1–5) habe ich selbst nachgewiesen. Bei einer Nachprüfung reicht AK12 (README und Test) und die geänderte Spec-Kopfzeile, solange am Produktivcode nichts weiter umgebaut wird.
