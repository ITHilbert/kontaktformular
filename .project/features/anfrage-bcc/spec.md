# Feature: Kopie jeder Anfrage an das Lead-Postfach (BCC)

## Ausgangslage
Jede Formular-Anfrage geht heute nur an `kontaktformular.mailTo`. Für die Marketing-Auswertung (Lead-Skript im Repo `ads-warehouse`) soll jede Anfrage zusätzlich als Kopie im Lead-Postfach `marketing@it-entwickler-hilbert.de` landen. Das Skript parst die Zeilen `Nummer:`, `Datum:` und `Site:` im Text-Teil und akzeptiert nur Mails mit `dkim=pass`.
Anlass: Dashboard-Task #84 (Projekt kontaktformular).

## Akzeptanzkriterien
- **AK1 — Kopie per BCC:** Gegeben die Config `kontaktformular.mailBcc` (env `KONTAKTFORMULAR_MAIL_BCC`) mit einer oder mehreren Adressen — Wenn eine gültige Anfrage abgeschickt wird — Dann hat die Mail `Anfrage` jede dieser Adressen als BCC-Empfänger, und der Empfänger `mailTo` bleibt unverändert.
- **AK2 — Ohne Einstellung wie bisher:** Gegeben `mailBcc` ist nicht gesetzt oder leer — Wenn eine gültige Anfrage abgeschickt wird — Dann hat die Mail keinen BCC-Empfänger.
- **AK3 — Format unverändert:** Wenn eine Anfrage mit oder ohne `mailBcc` verschickt wird — Dann sind Absender (`mailFrom`), Betreff, Reply-To und der Inhalt von HTML- und Text-Teil gleich wie ohne BCC; insbesondere bleiben die Zeilen `Nummer:`, `Datum:` und `Site:` im Text-Teil unverändert.
- **AK4 — Dokumentiert:** Die README nennt den Schlüssel `mailBcc` / `KONTAKTFORMULAR_MAIL_BCC`, das Format für mehrere Adressen und den Zweck (Lead-Postfach).

## Randfälle
- Wert als Array in der veröffentlichten Config (`['a@x.de', 'b@y.de']`) → alle Adressen sind BCC.
- Wert als String mit mehreren Adressen, durch Komma getrennt (`a@x.de, b@y.de`) → beide Adressen sind BCC, Leerzeichen um die Adressen werden ignoriert, leere Teile (`a@x.de,`) ergeben keinen Empfänger.
- Bot-Anfragen (Honeypot, zu kurze Nachricht) → wie bisher keine Mail, also auch keine Kopie.

## Nicht-Ziele
- Kontaktformular 7 auf den WordPress-Seiten (excel-experte.de, access-experte.de, access-programmierung.de).
- Rollout auf die Sites (`.env` setzen, Test-Anfrage, DKIM prüfen): erst nach Freigabe durch den User, nicht Teil dieses Branches.
- Release/Tag: nur auf Ansage des Users.
- Prüfen der Adressen auf Gültigkeit beim Booten (eine falsche Adresse meldet der Mailer).

## Entscheidungsspielraum
- Ob `bcc()` im Mailable (`build()`) oder im Controller (`Mail::to()->bcc()`) gesetzt wird, entscheidet der Coder. Bevorzugt im Mailable, damit jeder Versandweg der `Anfrage` die Kopie mitnimmt.
- Keine neuen Pakete.
- DKIM: Das Paket signiert nicht selbst. Die Signatur macht der Mailserver der Site, und ein BCC-Empfänger ändert sie nicht. Geprüft wird das beim Rollout.
