## In Arbeit
- [webmcp-formular](webmcp-formular/spec.md) — #1425 WebMCP-Attribute (toolname, tooldescription, toolparamdescription) am Formular für KI-Agenten
- [anfrage-website-feld](anfrage-website-feld/spec.md) — #1312 optionales Feld „Adresse Ihrer Website“, Nachricht als Kann-Angabe (per Config)
- [anhaenge-disk](anhaenge-disk/spec.md) — Anhänge über die Storage-Disk, Löschung eingeplant (Dashboard #119)
- [csrf-token](csrf-token/spec.md) — #120 CSRF-Token vor dem Absenden auffrischen (Token-Route, Vue-Komponente)

## Geplant
- Spalte `url` ist `string(255)`, Validierung von `site` erlaubt 2048 → lange Seitenadressen scheitern im strikten SQL-Modus (Anfrage geht verloren) (Doku 2026-10-26)
- `nummer` per `max+1` ohne Unique-Index/Sperre (Race-Condition bei gleichzeitigen Anfragen)
- README-Abschnitt „Routen“ dupliziert `.project/doku/07_API-Definitionen.md` und lässt `throttle` weg → auf das Leitdokument verweisen
- Leitdokumente `06_Prozessdefinitionen.md` (Löschbefehl, Anhänge) und `13_Glossar.md` (Herkunft, First Touch, Klick-ID) fehlen
- anfrage-herkunft: Test-Helfer `phpKlickId()`/`besuche()` bauen auf `Request::create()` (wendet `html_entity_decode` an) – weicht bei `&amp;`-Formen vom echten Request ab (Review 4)
- anfrage-herkunft: `herkunft` steht unnötig in `$fillable` des Models
- Laravel legt die volle URL samt `gclid` in `_previous.url` der Session ab (nur relevant beim Session-Treiber `cookie`)
- Pint-Altbestand im Paket (lang, Commands, Routes/web.php, Migration 2023, Seeder)
- anfrage-bcc: ungültige Adresse in `mailBcc` wirft RfcComplianceException → keine Mail an `mailTo`; der Fehler wird gemeldet (Review 2026-10-26); Vorschlag: ungültige BCC-Adressen verwerfen und loggen
- anfrage-bcc: kein Test prüft den Envelope der versendeten Mail (nur Mailable unter Mail::fake)
- Text-Teil der Mail maskiert HTML (`{{ }}` in `anfrage_plain.blade.php`) – relevant fürs Lead-Skript
- AK4-Test von loeschfrist prüft nur Ziffern, nicht die Beschriftung (Review-Hinweis)
- Command lädt Treffer per `get()` → bei großen Beständen `chunkById()`
- Eigene `pint.json` fürs Paket (Host-Preset meldet Paketstil)

## Fertig
- [mailversand](mailversand/spec.md) — #316 Versandfehler nach gespeicherter Anfrage: Danke-Seite statt 500, Fehler gemeldet (2026-11-02)
- [herkunft-schalter](herkunft-schalter/spec.md) — #110 Herkunftserfassung per Config abschaltbar (`herkunft_erfassen`) (2026-11-02)
- [danke-seiten-robots](danke-seiten-robots/plan.md) — Danke-Seiten bringen ihr Robots-Meta selbst mit, keine Host-View `header.robots_disallow` mehr nötig (2026-10-27)
- [anfrage-herkunft](anfrage-herkunft/plan.md) — Herkunft (gclid/UTM, Einstieg, Referrer) serverseitig in der Session, Spalte `herkunft`, Zeilen in der Anfrage-Mail (Dashboard #85) (2026-10-26)
- [anfrage-bcc](anfrage-bcc/plan.md) — Kopie jeder Anfrage per BCC an das Lead-Postfach (Dashboard #84) (2026-10-26)
- [download-route](download-route/plan.md) — Tests für die Download-Route (404/410/Download) (2026-10-23)
- [loeschfrist](loeschfrist/plan.md) — Einstellbare Löschfrist für Anfragen und Anhänge (2026-10-21)
