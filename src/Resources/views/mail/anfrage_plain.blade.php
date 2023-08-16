Kontaktformular
Nummer: {{ $kontakt->nummer }}
Name: {{ $kontakt->name }}
E-Mail: {{ $kontakt->email }}
Telefon: {{ $kontakt->telefon }}
Mitteilung: {{ $kontakt->nachricht }}
@if($kontakt->file_name)
Datei: {{$kontakt->file_name}}.{{$kontakt->file_type}} - Link: {{ $fileUrl }}
@endif
Datenverarbeitung: {{ $kontakt->datenverarbeitung == 1 ? 'ja' : 'nein'}}
Datum: {{ $kontakt->created_at }}
Site: {{ $kontakt->url }}
