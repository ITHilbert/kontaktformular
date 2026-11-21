Kontaktformular
Nummer: {!! $kontakt->nummer !!}
Name: {!! $kontakt->name !!}
E-Mail: {!! $kontakt->email !!}
@if(!empty($kontakt->telefon))
Telefon: {!! $kontakt->telefon !!}
@endif
@if(!empty($kontakt->website))
Website: {!! $kontakt->website !!}
@endif
@if(trim((string) $kontakt->nachricht) !== '')
Mitteilung: {!! $kontakt->nachricht !!}
@endif
@if($kontakt->file_name)
Datei: {!!$kontakt->file_name!!}.{!!$kontakt->file_type!!} - Link: {!! $fileUrl !!}
@endif
Datenverarbeitung: {!! $kontakt->datenverarbeitung == 1 ? 'ja' : 'nein' !!}
Datum: {!! $kontakt->created_at !!}
Site: {!! $kontakt->url !!}
@foreach($herkunftZeilen as $bezeichnung => $wert)
{!! $bezeichnung !!}: {!! $wert !!}
@endforeach
