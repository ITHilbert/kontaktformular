<?php
namespace ITHilbert\Kontaktformular\Controllers;

use Illuminate\Routing\Controller;
use ITHilbert\Kontaktformular\Mail\Anfrage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class KontaktformularController extends Controller
{

    public function anfrage(Request $request){
        $request->validate([
            'Name' => 'required',
            'Email' => 'required',
            'Telefon' => 'required',
            'Nachricht' => 'required',
            'Datenverarbeitung' => 'required',
            'site' => 'required',
        ]);


        Mail::to(config('kontaktformular.mailTo'))->send(new Anfrage($request));

        return redirect(route('danke_formular'));
    }

    public function danke_formular(){
        $active = 'index';
        return view('kontaktformular::danke_formular')->with(compact('active'));
    }

}
