<h1>Kontaktformular</h1>
Nummer: {{ $kontakt->nummer }}<br>
Name: {{ $kontakt->name }}<br>
E-Mail: <a href="mailto:{{ $kontakt->email }}">{{ $kontakt->email }}</a><br>
Telefon: {{ $kontakt->telefon }}<br>
Mitteilung: {{ $kontakt->nachricht }}<br>
@if($kontakt->file_name)
    Datei: <a href="{{ $fileUrl }}">{{$kontakt->file_name}}.{{$kontakt->file_type}} - einmal Link</a><br>
@endif
Datenverarbeitung: {{ $kontakt->datenverarbeitung == 1 ? 'ja' : 'nein'}}<br>
Datum: {{ $kontakt->created_at }}<br>
Site: {{ $kontakt->url }}<br>

