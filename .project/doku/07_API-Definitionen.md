# API-Definitionen: ITHilbert Kontaktformular

Alle Routen liegen in `src/Routes/web.php` in der Middleware-Gruppe `web` (Session, CSRF). Die Pfade sind ohne Präfix.

| Methode | Pfad | Name | Controller-Methode | Middleware |
|---|---|---|---|---|
| POST | `anfrage` | `anfrage` | `anfrage` | `throttle:6,1` |
| GET | `danke-formular` | `danke_formular` | `danke_formular` | – |
| GET | `danke-bot-formular` | `danke_bot_formular` | `danke_bot_formular` | – |
| GET | `kontaktformular/file/{hash}/{name}/{id}` | `kontaktformular.file` | `file_download` | – |

## POST `anfrage`

Felder:

| Feld | Regel |
|---|---|
| `Name` | required, string, max 255 |
| `Email` | required, email, max 255 |
| `Telefon` | optional, string, max 50 |
| `Nachricht` | required, mindestens 10 Zeichen und ein Leerzeichen (sonst Bot-Weiterleitung) |
| `datenverarbeitung` | required, accepted |
| `site` | required, string, max 2048; wird ohne `gclid`, `gbraid`, `wbraid` gespeichert |
| `Datei` | optional, file, max 10240 KB, mimes `pdf,jpg,png,zip,doc,docx,xls,xlsx` |
| `fax` | Honeypot: ist das Feld gefüllt, erfolgt Weiterleitung auf `danke-bot-formular` ohne Speichern |

Verhalten:

- Honeypot oder Nachricht unter der Mindestlänge: Redirect auf `danke_bot_formular`, nichts wird gespeichert oder versendet.
- Validierungsfehler: Standard-Redirect von Laravel mit Fehlermeldungen.
- Erfolg: Anfrage wird gespeichert, Mail an `mailTo` versendet, Redirect auf `danke_formular`.
- Die Herkunft kommt nicht aus dem Request, sondern aus der Session (Key `kontaktformular_herkunft`).

## GET `kontaktformular/file/{hash}/{name}/{id}`

- Die Anfrage mit `id` muss existieren und `file_hash` sowie `file_name` müssen zu `hash` und `name` passen; sonst 404.
- Die ZIP-Datei `storage/app/kontaktformular/{hash}.zip` fehlt: 410.
- Erfolg: Download als `{name}.zip`, `file_downloaded_at` wird gesetzt, die Datei bleibt bestehen.

## GET `danke-formular` und `danke-bot-formular`

Reine Anzeige-Seiten. `danke-bot-formular` erscheint als Erfolg, obwohl nichts gespeichert wurde.
