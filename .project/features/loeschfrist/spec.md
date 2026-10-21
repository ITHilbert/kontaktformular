# Feature: Einstellbare Löschfrist für Anfragen und Anhänge

## Ausgangslage
`kontaktformular:delete_old_files` löscht Anhänge (`storage/app/kontaktformular/<hash>.zip`) fest nach 30 Tagen und setzt `file_deleted_at`. Die Anfragen selbst (Tabelle `kontaktformular`: Name, E-Mail, Telefon, Nachricht) bleiben unbegrenzt gespeichert. Für die DSGVO (Speicherbegrenzung, Art. 5 Abs. 1 lit. e) braucht jedes Projekt eine Löschfrist. Das Paket wird von rund elf Projekten genutzt — deren Verhalten darf sich ohne eigene Einstellung nicht ändern.
Anlass: it-hilbert.com, Löschfrist 1 Monat (User, 2026-10-21).

## Akzeptanzkriterien
- **AK1 — Einstellbar:** Gegeben die Config `kontaktformular.dateien_loeschen_nach_tagen` (env `KONTAKTFORMULAR_DATEIEN_LOESCHEN_NACH_TAGEN`, Standard `30`) und `kontaktformular.anfragen_loeschen_nach_tagen` (env `KONTAKTFORMULAR_ANFRAGEN_LOESCHEN_NACH_TAGEN`, Standard `null`) — Wenn keine davon gesetzt ist — Dann verhält sich `kontaktformular:delete_old_files` wie bisher: Anhänge älter als 30 Tage werden gelöscht, Anfragen bleiben.
- **AK2 — Anfragen löschen:** Gegeben `anfragen_loeschen_nach_tagen = N` — Wenn der Command läuft — Dann sind alle Anfragen mit `created_at` älter als N Tage aus der Tabelle gelöscht, samt ihres Anhangs auf der Platte; jüngere bleiben unverändert.
- **AK3 — Anhänge nach eigener Frist:** Gegeben `dateien_loeschen_nach_tagen = M` — Wenn der Command läuft — Dann sind Anhänge älter als M Tage gelöscht und `file_deleted_at` gesetzt; der Datensatz bleibt (sofern AK2 ihn nicht löscht).
- **AK4 — Rückmeldung:** Wenn der Command läuft — Dann nennt er die Zahl gelöschter Anhänge und Anfragen und endet mit Exit-Code 0.

## Randfälle
- Anhang-Datei fehlt bereits → kein Fehler, Datensatz wird trotzdem behandelt.
- `anfragen_loeschen_nach_tagen` kleiner als `dateien_loeschen_nach_tagen` → Anfrage samt Anhang wird nach der kürzeren Frist gelöscht.
- Ungültiger Wert (0, negativ, kein Integer) → Command bricht mit verständlicher Meldung und Exit-Code ≠ 0 ab, löscht nichts.

## Nicht-Ziele
- Einplanen im Scheduler (macht jedes Projekt selbst).
- Anonymisieren statt Löschen.
- Umbenennen des Commands (Name bleibt aus Kompatibilität).
- Löschen der versendeten E-Mails im Postfach.
