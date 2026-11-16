# Feature: Optionales Feld „Adresse Ihrer Website“ und Nachricht als Kann-Angabe (per Config)

Dashboard-Task #1312 (kontaktformular). Risiko: kritisch — Kundendaten (neue personenbezogene Angabe, gespeichert und per Mail versendet); nach Review-Fund hochgestuft (automode, selbst entschieden).

## Ausgangslage
Das Formular verlangt eine Nachricht (`required`, dazu eine Bot-Heuristik: mindestens 10 Zeichen und ein Leerzeichen, sonst Bot-Danke-Seite). Ein Feld für die Website des Anfragenden fehlt; die Spalte `url` speichert die Herkunftsseite (`site`), nicht die Website des Kunden. Anlass: Marketing-Briefing homepage-betreuung.com — Betreiber kleiner Websites können ihr Problem schwer aufschreiben, die Adresse der Website ist für die Erstanalyse wichtiger als ein langer Text. Beide Neuerungen sind per Config schaltbar; die Standards lassen alle bestehenden Hosts unverändert.

## Akzeptanzkriterien
- **AK1 — Config-Schlüssel und Standards:** Gegeben die Config `config/kontaktformular.php` des Pakets — Dann gibt es `feld_website` (bool, Standard `false`, env `KONTAKTFORMULAR_FELD_WEBSITE`) und `nachricht_pflicht` (bool, Standard `true`, env `KONTAKTFORMULAR_NACHRICHT_PFLICHT`); beide folgen der Auswertung von `herkunft_erfassen` (aus bei `false`, `0`, `off`, `no` oder leerem Wert, sonst an).
- **AK2 — Website wird gespeichert:** Gegeben `feld_website` ist `true` — Wenn eine gültige Anfrage mit dem Feld `Website` (z. B. `https://example.org/shop`) abgeschickt wird — Dann steht der Wert in der neuen, nullbaren Spalte `website` der Tabelle `kontaktformular` (Migration im Paket, `string` mit 2048 Zeichen).
- **AK3 — Website bleibt optional und begrenzt:** Gegeben `feld_website` ist `true` — Wenn `Website` fehlt oder leer ist, wird die Anfrage normal gespeichert und `website` bleibt `null`; ist `Website` länger als 2048 Zeichen, lehnt die Validierung die Anfrage ab (Rückkehr zum Formular mit Fehler, kein Datensatz, keine Mail). Ein Wert ohne Schema (`example.org`) wird angenommen.
- **AK4 — Website in der Mail:** Gegeben die Anfrage hat eine `website` — Wenn die Mail `Anfrage` verschickt wird — Dann enthalten HTML- und Text-Teil direkt nach der Zeile `E-Mail:` bzw. `Telefon:` (also vor `Mitteilung:`) eine Zeile `Website:` mit dem Wert; HTML-Zeichen sind maskiert wie bei den anderen Feldern. Hat die Anfrage keine `website`, steht keine Zeile `Website:` in der Mail.
- **AK5 — Zeilenumbrüche neutralisiert:** Gegeben `feld_website` ist `true` — Wenn `Website` Zeilenumbrüche (`\r`, `\n`) enthält — Dann werden sie beim Speichern durch je ein Leerzeichen ersetzt, sodass im Text-Teil der Mail keine zusätzliche Zeile (z. B. eine eingeschleuste Zeile `Site: …`) entstehen kann.
- **AK6 — Standard `feld_website=false` wie bisher:** Gegeben `feld_website` ist `false` oder nicht gesetzt — Wenn eine Anfrage mit einem Feld `Website` abgeschickt wird — Dann wird der Wert weder gespeichert noch in der Mail ausgegeben, und die Anfrage funktioniert auch auf einer Tabelle ohne die Spalte `website` (Host hat die neue Migration noch nicht ausgeführt).
- **AK7 — Standard `nachricht_pflicht=true` wie bisher:** Gegeben `nachricht_pflicht` ist `true` oder nicht gesetzt — Wenn eine Anfrage ohne oder mit leerer `Nachricht` abgeschickt wird — Dann verhält sie sich wie bisher: Weiterleitung zur Bot-Danke-Seite, kein Datensatz, keine Mail.
- **AK8 — Nachricht als Kann-Angabe:** Gegeben `nachricht_pflicht` ist `false` — Wenn eine Anfrage ohne oder mit leerer `Nachricht` abgeschickt wird (sonst gültig) — Dann wird sie gespeichert (`nachricht` = leerer String), die Mail verschickt und zur Danke-Seite (`danke_formular`) weitergeleitet. In der Mail steht dann keine Zeile `Mitteilung:`; mit Nachricht bleibt sie wie bisher.
- **AK9 — Gefüllte Nachricht wird weiter geprüft:** Gegeben `nachricht_pflicht` ist `false` — Wenn die `Nachricht` gefüllt, aber kürzer als 10 Zeichen ist oder kein Leerzeichen enthält — Dann gilt die bisherige Bot-Heuristik (Weiterleitung zur Bot-Danke-Seite, kein Datensatz, keine Mail). Der Honeypot `fax` greift in beiden Einstellungen wie bisher.
- **AK10 — Vue-Komponente:** Gegeben die Komponente `src/Resources/Vue/kontaktformular.vue` — Dann hat sie die Props `feld-website` (Boolean, Standard `false`) und `nachricht-pflicht` (Boolean, Standard `true`). Mit `feld-website` zeigt sie nach der Telefonnummer ein optionales Feld `Website` (Label „Adresse Ihrer Website“, `type="text"` mit `inputmode="url"`, `maxlength="2048"`, kein `required`); ohne Prop erscheint kein solches Feld. Mit `nachricht-pflicht=false` hat das Nachricht-Feld kein `required`, das Label „Ihre Nachricht“ ohne `*` und mit dem Zusatz „(optional)“; ohne die Einstellung bleibt es wie heute.
- **AK11 — Standard-View reicht die Config durch:** Wenn die mitgelieferte View `kontaktformular::show` gerendert wird — Dann übergibt sie `feld-website` und `nachricht-pflicht` entsprechend der Config an die Komponente.
- **AK12 — Dokumentiert:** Die README nennt beide Schlüssel samt env-Namen, Standards und die Props der Komponente, beschreibt die neue Spalte `website` (Migration ausführen, bevor `feld_website` an ist), das Verhalten der Bot-Heuristik bei `nachricht_pflicht=false` (AK9), den Hinweis „Hosts mit eigener Kopie der Komponente bauen das Feld selbst ein“ und den Hinweis, dass die Datenschutzerklärung des Hosts das neue Feld nennen muss.
- **AK13 — Gespeicherte Nachricht ist getrimmt (Ergänzt 2026-11-16, #1325):** Wenn eine Anfrage mit einer Nachricht abgeschickt wird, die Leerzeichen oder Zeilenumbrüche am Anfang oder Ende hat — Dann werden sie beim Speichern entfernt (`trim`), in beiden Einstellungen von `nachricht_pflicht`; der Inhalt zwischen den Rändern bleibt unverändert. README nennt das Verhalten. *Entscheidung: Variante A des Tasks (getrimmt beibehalten); gilt für alle Hosts, auch bei Standard-Config, harmlos und sauberere Daten.*
- **AK14 — Nachricht keine Zeichenkette (Ergänzt 2026-11-16, #1327):** Wenn `Nachricht` als Liste (`Nachricht[]=a`) oder sonst keine Zeichenkette gesendet wird — Dann leitet der Controller zur Bot-Danke-Seite (`danke_bot_formular`) weiter, ohne Datensatz und ohne Mail, in beiden Einstellungen von `nachricht_pflicht`; es gibt keinen Serverfehler (500). Eine gültige Nachricht wird wie bisher gespeichert.

## Randfälle
- `Website` mit nur Leerzeichen → gilt als leer (`website` bleibt `null`).
- `nachricht_pflicht=false` und `Nachricht` besteht nur aus Leerzeichen → gilt als leer (AK8).
- `feld_website=true` ohne ausgeführte Migration → Datenbankfehler beim Speichern; die README nennt die Reihenfolge (AK12), kein Abfangen im Code.
- Bestehende Datensätze → `website` bleibt `null`, kein Nachbefüllen.
- Mail-Betreff, Absender, Reply-To und die Zeilen `Nummer:`, `Datum:`, `Site:` im Text-Teil bleiben unverändert.

## Nicht-Ziele
- Format-Prüfung der Website-Adresse (URL-Validierung): Kundinnen und Kunden tippen oft `example.org` ohne Schema; geprüft wird nur die Länge.
- Eigene Kopien der Komponente in Hosts (homepage-betreuung.com baut das Feld selbst nach dem Release ein, eigener Task in Projekt 29).
- Datenschutzerklärung und Rechtsprüfung der Hosts (Hinweis in der README, AK12).
- Release/Tag des Pakets außerhalb des automode-Laufs.

## Entscheidungsspielraum
- Namen der Testdateien, interne Struktur im Controller (z. B. Hilfsmethode für die Nachrichten-Prüfung); keine neuen Pakete.
- Die Spalte `website` ist neu und nullbar (`nachricht` bleibt `NOT NULL`, bei Kann-Angabe wird `''` gespeichert — keine Änderung am Bestandsschema).

## Annahmen (Nachtschicht-/automode-Stil, selbst entschieden)
- Textfeld mit `inputmode="url"` statt `type="url"`, weil der Browser bei `type="url"` ein Schema verlangt (Hürde für die Zielgruppe).
- Bei `nachricht_pflicht=false` bleibt die Bot-Heuristik für gefüllte Nachrichten (AK9), damit der Spamschutz nicht schwächer wird; Umkehr: Heuristik nur bei Pflicht anwenden.
- Die Zeilenumbruch-Neutralisierung gilt nur für das neue Feld (AK5); die Altfelder sind nicht Teil dieses Tasks.
