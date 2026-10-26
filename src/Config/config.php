<?php

declare(strict_types=1);

return [
    'name' => 'Kontaktformular',
    'mailFrom' => env('KONTAKTFORMULAR_MAIL_FROM', env('MAIL_FROM_ADDRESS', 'kontaktformular@example.com')),
    'mailTo' => env('KONTAKTFORMULAR_MAIL_TO', env('MAIL_TO_ADDRESS', 'kontakt@example.com')),
    // Kopie jeder Anfrage (BCC): String (mehrere Adressen mit Komma getrennt) oder Array; leer = keine Kopie.
    'mailBcc' => env('KONTAKTFORMULAR_MAIL_BCC', ''),
    'subject' => env('KONTAKTFORMULAR_SUBJECT', 'Anfrage von '.env('APP_NAME', 'Webseite')),
    'fileDownloadUrl' => env('APP_URL').'/kontaktformular/file/',

    // Anhänge werden nach dieser Zahl Tage gelöscht (positive ganze Zahl), die Anfrage bleibt erhalten.
    'dateien_loeschen_nach_tagen' => env('KONTAKTFORMULAR_DATEIEN_LOESCHEN_NACH_TAGEN', 30),
    // Anfragen samt Anhang werden nach dieser Zahl Tage gelöscht; null = Anfragen nie löschen.
    'anfragen_loeschen_nach_tagen' => env('KONTAKTFORMULAR_ANFRAGEN_LOESCHEN_NACH_TAGEN'),
];
