<?php

return [
    'name' => 'Kontaktformular',
    'mailFrom' => 'kontaktformular@from.com',
    'mailTo'   => 'kontakt@to.com',
    'subject'  => 'Anfrage von '. env('APP_NAME'),
    'fileDownloadUrl' => env('APP_URL').'/kontaktformular/file/',
];
