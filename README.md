# ITHilbert Kontaktformular

**Beschreibung**: Mein Kontaktformular mit Verarbeitungscode.

## Dokumentation

Die ausführliche Dokumentation befindet sich im Ordner `docs/`:
- [Kontext & Zielsetzung](docs/00_README_Kontext.md)
- [Architekturübersicht](docs/01_Architekturübersicht.md)
- [Modulstruktur](docs/07_Modulstruktur.md)

## Installation

```bash
composer require ithilbert/kontaktformular
```

## Konfiguration

```php
return [
    'mailTo' => [env('MAIL_TO_ADDRESS', 'ihre-email@example.com')],
];
```

## Namespace
`ITHilbert\Kontaktformular`

## Routen

```php
Route::post('/kontaktformular/anfrage', [KontaktformularController::class, 'anfrage'])->name('anfrage');
Route::get('/kontaktformular/danke', [KontaktformularController::class, 'danke_formular'])->name('danke_formular');
Route::get('/kontaktformular/danke_bot', [KontaktformularController::class, 'danke_bot_formular'])->name('danke_bot_formular');
```

## Views
Es müssen die Views 'layouts.master' und 'layouts.masterBot' im Hauptprojekt vorhanden sein.
