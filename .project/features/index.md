## In Arbeit

## Geplant
- anfrage-herkunft: Test-Helfer `phpKlickId()`/`besuche()` bauen auf `Request::create()` (wendet `html_entity_decode` an) – weicht bei `&amp;`-Formen vom echten Request ab (Review 4)
- anfrage-herkunft: `herkunft` steht unnötig in `$fillable` des Models
- Laravel legt die volle URL samt `gclid` in `_previous.url` der Session ab (nur relevant beim Session-Treiber `cookie`)
- Pint-Altbestand im Paket (lang, Commands, Routes/web.php, Migration 2023, Seeder)
- anfrage-bcc: ungültige Adresse in `mailBcc` wirft RfcComplianceException → auch `mailTo` bekommt keine Mail, Besucher sieht 500 (Review 2026-10-26); Vorschlag: ungültige BCC-Adressen verwerfen und loggen
- anfrage-bcc: kein Test prüft den Envelope der versendeten Mail (nur Mailable unter Mail::fake)
- Text-Teil der Mail maskiert HTML (`{{ }}` in `anfrage_plain.blade.php`) – relevant fürs Lead-Skript
- AK4-Test von loeschfrist prüft nur Ziffern, nicht die Beschriftung (Review-Hinweis)
- Command lädt Treffer per `get()` → bei großen Beständen `chunkById()`
- Speicherpfad `storage/app/kontaktformular` fest; Projekte mit Laravel-11-Skelett (`storage/app/private`) prüfen
- Eigene `pint.json` fürs Paket (Host-Preset meldet Paketstil)

## Fertig
- [anfrage-herkunft](anfrage-herkunft/plan.md) — Herkunft (gclid/UTM, Einstieg, Referrer) serverseitig in der Session, Spalte `herkunft`, Zeilen in der Anfrage-Mail (Dashboard #85) (2026-10-26)
- [anfrage-bcc](anfrage-bcc/plan.md) — Kopie jeder Anfrage per BCC an das Lead-Postfach (Dashboard #84) (2026-10-26)
- [download-route](download-route/plan.md) — Tests für die Download-Route (404/410/Download) (2026-10-23)
- [loeschfrist](loeschfrist/plan.md) — Einstellbare Löschfrist für Anfragen und Anhänge (2026-10-21)
