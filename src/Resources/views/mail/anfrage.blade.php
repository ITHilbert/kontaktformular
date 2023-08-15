<h1>Kontaktformular</h1>
Nummer: {{ $kontakt->nummer }}<br>
Name: {{ $kontakt->name }}<br>
E-Mail: <a href="mailto:{{ $kontakt->email }}">{{ $kontakt->email }}</a><br>
Telefon: {{ $kontakt->telefon }}<br>
Mitteilung: {{ $kontakt->mitteilung }}<br>
@if($kontakt->filename)
    Datei: <a href="{{ $fileUrl }}">{{$kontakt->filename}}.{{$kontakt->filetype}} - einmal Link</a><br>
@endif
Datenverarbeitung: {{ $kontakt->datenverarbeitung }}<br>
Site: {{ $kontakt->url }}<br>

