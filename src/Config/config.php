<?php

declare(strict_types=1);

return [
    'name'            => 'Kontaktformular',
    'mailFrom'        => env('KONTAKTFORMULAR_MAIL_FROM', env('MAIL_FROM_ADDRESS', 'kontaktformular@example.com')),
    'mailTo'          => env('KONTAKTFORMULAR_MAIL_TO', env('MAIL_TO_ADDRESS', 'kontakt@example.com')),
    'subject'         => env('KONTAKTFORMULAR_SUBJECT', 'Anfrage von ' . env('APP_NAME', 'Webseite')),
    'fileDownloadUrl' => env('APP_URL') . '/kontaktformular/file/',
];
