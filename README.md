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
];
```

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
