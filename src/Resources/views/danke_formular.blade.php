@extends('layouts.master')

@section('head.titel', config('site.firma'). " – Danke für Ihre Nachricht")
@section('head.keywords', "")
@section('head.description', config('site.firma') .", Danke für Ihre Nachricht")
@section('meta')
    @include('header.robots_disallow')
@stop


@section('content')
<section class="section border-0 m-0 inhalt">
    <div class="container">
        <div class="row">
            <div class="col-md-10 mb-10">
                <h2>Vielen Dank für Ihre Anfrage.</h2>
                <p>Wir haben soeben Ihre Anfrage via Email erhalten. Wir melden uns bei Ihnen so schnell wie möglich.</p>
                <p>Viele Grüße</p>
                <p>
                    Ihr Team von
                    IT-Hilbert GmbH
                </p>
            </div>
        </div>
    </div>
</section>
@endsection

