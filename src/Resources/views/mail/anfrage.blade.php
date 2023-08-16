<html>
<h1>Kontaktformular</h1>
<table>
    <tr>
        <td><b>Nummer:</b></td>
        <td>{{ $kontakt->nummer }}</td>
    </tr>
    <tr>
        <td><b>Name:</b></td>
        <td>{{ $kontakt->name }}</td>
    </tr>
    <tr>
        <td><b>E-Mail:</b></td>
        <td><a href="mailto:{{ $kontakt->email }}">{{ $kontakt->email }}</a></td>
    </tr>
    <tr>
        <td><b>Telefon:</b></td>
        <td>{{ $kontakt->telefon }}</td>
    </tr>
    <tr>
        <td><b>Mitteilung:</b></td>
        <td>{{ $kontakt->nachricht }}</td>
    </tr>
    @if($kontakt->file_name)
        <tr>
            <td><b>Datei:</b></td>
            <td><a href="{{ $fileUrl }}">{{$kontakt->file_name}}.{{$kontakt->file_type}} - einmal Link</a></td>
        </tr>
    @endif
    <tr>
        <td><b>Datenverarbeitung:</b></td>
        <td>{{ $kontakt->datenverarbeitung == 1 ? 'ja' : 'nein'}}</td>
    </tr>
    <tr>
        <td><b>Datum:</b></td>
        <td>{{ $kontakt->created_at }}</td>
    </tr>
    <tr>
        <td><b>Site:</b></td>
        <td>{{ $kontakt->url }}</td>
    </tr>
</table>
</html>
