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
    'subject'         => env('KONTAKTFORMULAR_SUBJECT', 'Anfrage von ' . env('APP_NAME')),
    'fileDownloadUrl' => env('APP_URL') . '/kontaktformular/file/',
    'dateien_loeschen_nach_tagen'  => env('KONTAKTFORMULAR_DATEIEN_LOESCHEN_NACH_TAGEN', 30),
    'anfragen_loeschen_nach_tagen' => env('KONTAKTFORMULAR_ANFRAGEN_LOESCHEN_NACH_TAGEN'),
];
```

## Löschfristen

| Einstellung (`.env`) | Standard | Wirkung |
|---|---|---|
| `KONTAKTFORMULAR_DATEIEN_LOESCHEN_NACH_TAGEN` | `30` | Anhänge älter als die Frist werden gelöscht, die Anfrage bleibt (`file_deleted_at` wird gesetzt). |
| `KONTAKTFORMULAR_ANFRAGEN_LOESCHEN_NACH_TAGEN` | nicht gesetzt | Anfragen älter als die Frist werden samt Anhang gelöscht. Ohne Wert bleiben Anfragen dauerhaft erhalten. |

Beide Werte müssen positive ganze Zahlen sein. Bei ungültigem Wert bricht der Command mit Fehlermeldung ab (Exit-Code 1) und löscht nichts.

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
