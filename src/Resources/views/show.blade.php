<div class="card card-mute py-4">
<h2 class="text-center m-0">Kontaktformular</h2>
    <div class="card-body px-4">
        <kontaktformular route="{{ route('anfrage') }}" token-route="{{ route('kontaktformular.csrf_token') }}" :feld-website="{{ config('kontaktformular.feld_website') ? 'true' : 'false' }}" :nachricht-pflicht="{{ (config('kontaktformular.nachricht_pflicht') ?? true) ? 'true' : 'false' }}" @if(config('kontaktformular.webmcp_name')) tool-name="{{ config('kontaktformular.webmcp_name') }}"@endif @if(config('kontaktformular.webmcp_beschreibung')) tool-description="{{ config('kontaktformular.webmcp_beschreibung') }}"@endif></kontaktformular>
    </div>
</div>
