# ITHilbert Kontaktformular

**Beschreibung**: Kontaktformular mit Spam-Schutz, optionalem Datei-Upload und Mail-Versand.

## Features

- Felder: Name, E-Mail, Telefon (optional), Nachricht, Datei-Anhang (optional)
- Spam-Schutz: Honeypot-Feld + Mindest-Nachrichtenlänge + Rate-Limit (`throttle:6,1`)
- Datei-Upload mit Whitelist (`pdf,jpg,png,zip,doc,docx,xls,xlsx`), automatisches Zippen
- Token-basierter Download-Link mit Hash + Name + ID
- Cleanup-Command für alte Dateien (`kontaktformular:delete_old_files`)

## Installation

```bash
composer require ithilbert/kontaktformular
php artisan vendor:publish --tag=kontaktformular-config
php artisan migrate
```

## Konfiguration

```php
// config/kontaktformular.php
return [
    'mailFrom'        => env('KONTAKTFORMULAR_MAIL_FROM', env('MAIL_FROM_ADDRESS')),
    'mailTo'          => env('KONTAKTFORMULAR_MAIL_TO',   env('MAIL_TO_ADDRESS')),
    'mailBcc'         => env('KONTAKTFORMULAR_MAIL_BCC', ''),
    'subject'         => env('KONTAKTFORMULAR_SUBJECT', 'Anfrage von ' . env('APP_NAME')),
    'fileDownloadUrl' => env('APP_URL') . '/kontaktformular/file/',
    'dateien_loeschen_nach_tagen'  => env('KONTAKTFORMULAR_DATEIEN_LOESCHEN_NACH_TAGEN', 30),
    'anfragen_loeschen_nach_tagen' => env('KONTAKTFORMULAR_ANFRAGEN_LOESCHEN_NACH_TAGEN'),
];
```

### Kopie an das Lead-Postfach (`mailBcc`)

`mailBcc` (env `KONTAKTFORMULAR_MAIL_BCC`) schickt jede Anfrage zusätzlich als BCC-Kopie an ein Lead-Postfach, z. B. `marketing@it-entwickler-hilbert.de` für die Marketing-Auswertung. `mailTo`, Absender, Betreff und Inhalt bleiben unverändert.

- Eine Adresse: `KONTAKTFORMULAR_MAIL_BCC=marketing@it-entwickler-hilbert.de`
- Mehrere Adressen: durch Komma getrennt (`a@x.de, b@y.de`); Leerzeichen und leere Teile werden ignoriert. In der veröffentlichten Config ist auch ein Array möglich (`['a@x.de', 'b@y.de']`).
- Leer oder nicht gesetzt: keine Kopie.

## Herkunft der Anfrage (Google Ads / UTM)

Das Paket merkt sich, woher ein Besucher kam, und schickt diese Herkunft mit der Anfrage mit – auch wenn er vor dem Formular weitere Seiten aufruft.

### Update eines bestehenden Projekts

- `php artisan migrate` ist beim Update **Pflicht** (neue Spalte `herkunft`); ohne sie schlägt das Speichern fehl, sobald eine Herkunft erfasst wurde.
- Wer die Mail-Views veröffentlicht hat (`php artisan vendor:publish --tag=kontaktformular-views`), muss sie **neu veröffentlichen** (`--force`) und eigene Anpassungen übernehmen – sonst fehlen die neuen Zeilen in der Mail.

### Middleware

Die Middleware `ITHilbert\Kontaktformular\Middleware\HerkunftErfassen` wird vom ServiceProvider **automatisch in die Middleware-Gruppe `web` eingehängt**, und zwar **direkt nach `StartSession`** (in der Gruppe und in der Middleware-Priorität). So wird die Herkunft auch erfasst, wenn CSRF-Prüfung, `auth` oder `throttle` den Request danach abbrechen. Middlewares, die andere Pakete in `web` einhängen (z. B. `userauth`), bleiben erhalten. Im Projekt ist nichts einzutragen. Routen außerhalb von `web` (z. B. `api`, ohne Session) bleiben unberührt.

Erfasst wird nur beim **ersten Request einer Session** (First Touch); spätere Requests ändern die gespeicherten Werte nicht. Ist der erste Request schon `POST /anfrage`, zählt dieser.

### Erfasste Werte

| Wert | Quelle |
|---|---|
| `gclid`, `gbraid`, `wbraid` | Query-Parameter (Google-Ads-Klick-IDs) |
| `utm_source`, `utm_medium`, `utm_campaign`, `utm_term` | Query-Parameter |
| `einstieg` | Pfad der ersten Seite ohne Query, URL-dekodiert, z. B. `/excel-programmierer`, `/über-uns` (statt `/%C3%BCber-uns`) |
| `referrer` | nur der Host aus dem `Referer`-Header, z. B. `google.com` |

- Nur vorhandene Werte werden gespeichert. Leere Parameter und Arrays (`?gclid[]=x`) werden ignoriert, Werte über 255 Zeichen auf 255 gekürzt.
- Ein Referrer mit dem eigenen Host (interne Navigation) oder ohne gültigen Host gilt als kein Referrer.

### Spalte `herkunft`

Die Migration des Pakets legt die nullable JSON-Spalte `herkunft` in `kontaktformular` an (`php artisan migrate`). Sie enthält die erfassten Werte, z. B. `{"gclid":"…","utm_source":"google","einstieg":"/excel-programmierer","referrer":"google.com"}`. Ohne gespeicherte Herkunft bleibt sie `null`; bestehende Anfragen werden nicht nachbefüllt.

### Zeilen in der Mail

HTML- und Text-Teil der Mail enthalten direkt nach `Site:` vier Zeilen:

```
Herkunft: google_ads
Einstieg: /excel-programmierer
Referrer: google.com
Klick-ID: gclid
```

- `Herkunft:` ist `google_ads`, wenn `gclid`, `gbraid` oder `wbraid` vorhanden ist oder `utm_source=google` und `utm_medium=cpc` (Groß-/Kleinschreibung egal); sonst `organisch/sonstige`, wenn ein Referrer vorhanden ist; sonst `direkt`.
- `Klick-ID:` nennt nur den **Namen** des Parameters (bei mehreren den ersten in der Reihenfolge `gclid`, `gbraid`, `wbraid`), nie seinen Wert.
- Fehlt ein Wert für `Einstieg:`, `Referrer:` oder `Klick-ID:`, steht dort `-`.
- Die Klick-IDs `gclid`, `gbraid` und `wbraid` werden beim Speichern aus der übermittelten Seitenadresse (Formularwert `site`, Spalte `url`, Zeile `Site:`) **entfernt**. Alle übrigen Bestandteile bleiben unverändert, auch `utm_*`; war die Klick-ID der einzige Parameter, entfällt das `?`.

### Hinweis für die Datenschutzerklärung

- Die Herkunft wird **serverseitig in der Laravel-Session** gemerkt (die es ohnehin gibt) und mit der Anfrage in der Datenbank gespeichert.
- Es werden **keine neuen Cookies** gesetzt; das Paket schreibt nichts in `localStorage` oder `sessionStorage` und liefert dafür kein JavaScript aus.
- Beim Session-Treiber `cookie` (`SESSION_DRIVER=cookie`) wird **nichts erfasst**, weil die Session-Daten dann im Browser lägen. Die Anfrage wird dann mit `herkunft = null` gespeichert, die Mail zeigt `direkt` bzw. `-`.
- Gespeichert werden die Klick-ID (z. B. `gclid`), UTM-Parameter, die Einstiegsseite und der Host der verweisenden Seite. Die Datenschutzerklärung der Site sollte diese Verarbeitung im Abschnitt zum Kontaktformular nennen.

## Löschfristen

| Einstellung (`.env`) | Standard | Wirkung |
|---|---|---|
| `KONTAKTFORMULAR_DATEIEN_LOESCHEN_NACH_TAGEN` | `30` | Anhänge älter als die Frist werden gelöscht, die Anfrage bleibt (`file_deleted_at` wird gesetzt). |
| `KONTAKTFORMULAR_ANFRAGEN_LOESCHEN_NACH_TAGEN` | nicht gesetzt | Anfragen älter als die Frist werden samt Anhang gelöscht. Ohne Wert bleiben Anfragen dauerhaft erhalten. |

Beide Werte müssen positive ganze Zahlen sein. Bei ungültigem Wert bricht der Command mit Fehlermeldung ab (Exit-Code 1) und löscht nichts.

**Achtung:** `KONTAKTFORMULAR_DATEIEN_LOESCHEN_NACH_TAGEN=` ohne Wert gilt als ungültig – dann stoppt auch die Löschung der Anhänge. Die Zeile entweder mit Zahl setzen oder ganz weglassen.

Anhänge liegen unter `storage/app/kontaktformular/`. Zeigt die Disk `local` des Projekts auf `storage/app/private` (Laravel-11-Skelett), findet der Command die Dateien dort nicht.

## Command

```bash
php artisan kontaktformular:delete_old_files
```

Löscht zuerst die Anfragen nach der Anfragenfrist, danach die Anhänge nach der Dateifrist, und gibt die Zahl gelöschter Anhänge und Anfragen aus. Eine bereits fehlende Anhang-Datei ist kein Fehler.

**Wichtig:** Das Paket plant den Command nicht selbst ein. Das Projekt muss ihn im Scheduler eintragen, z. B. `Schedule::command('kontaktformular:delete_old_files')->daily();`.

## Routen

```php
Route::post('anfrage', [KontaktformularController::class, 'anfrage'])->name('anfrage');
Route::get('danke-formular', [KontaktformularController::class, 'danke_formular'])->name('danke_formular');
Route::get('danke-bot-formular', [KontaktformularController::class, 'danke_bot_formular'])->name('danke_bot_formular');
Route::get('kontaktformular/file/{hash}/{name}/{id}', [KontaktformularController::class, 'file_download'])->name('kontaktformular.file');
```

## Views

Im Hauptprojekt müssen `layouts.master` und `layouts.masterBot` existieren.

## Namespace
`ITHilbert\Kontaktformular`
