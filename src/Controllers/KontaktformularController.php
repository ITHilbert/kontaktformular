<?php

namespace ITHilbert\Kontaktformular\Controllers;

use Illuminate\Routing\Controller;
use ITHilbert\Kontaktformular\Mail\Anfrage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use ITHilbert\Kontaktformular\Models\Kontaktformular;

class KontaktformularController extends Controller
{

    public function anfrage(Request $request)
    {
        $request->validate([
            'Name' => 'required',
            'Email' => 'required',
            'Telefon' => 'required',
            'Nachricht' => 'required',
            'Datenverarbeitung' => 'required',
            'site' => 'required',
        ]);

        $kontakt = new Kontaktformular();
        $kontakt->name = $request->Name;
        $kontakt->email = $request->Email;
        $kontakt->telefon = $request->Telefon;
        $kontakt->nachricht = $request->Nachricht;
        $kontakt->datenverarbeitung = $request->Datenverarbeitung;
        $kontakt->url = $request->site;

        // Prüfen, ob eine Datei hochgeladen wurde
        if ($request->hasFile('Datei')) {

            // Überprüfen Sie, ob der Upload erfolgreich war
            if ($request->file('Datei')->isValid()) {

                // Originalname der Datei
                $originalName = $request->file('Datei')->getClientOriginalName();
                //originalName ohne Dateiendung
                $originalNameOhneEndung = str_replace('.' . $request->file('Datei')->extension(), '', $originalName);

                // Datei speichern
                $path = $request->Datei->store('uploads');
                //Path ohne uploads und Dateiendung
                $path = str_replace('uploads/', '', $path);
                //Dateiendung ermitteln
                $dateityp = $request->Datei->extension();
                //Datei ohne Endung
                $path = str_replace('.' . $request->Datei->extension(), '', $path);

                if($dateityp != 'zip'){
                    //Datei zippen und speichern
                    $zip = new \ZipArchive();
                    $zip->open(storage_path('app/uploads/' . $path . '.zip'), \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
                    $zip->addFile(storage_path('app/uploads/' . $path . '.' . $dateityp), $originalName);
                    $zip->close();

                    //Ungezippte Datei löschen
                    unlink(storage_path('app/uploads/' . $path . '.' . $dateityp));
                }

                $kontakt->filename = $originalNameOhneEndung;
                $kontakt->filehash = $path;
                $kontakt->filetype = $dateityp;
                $kontakt->filesize = round($request->Datei->getSize() /(1024*1024),2);
            } else {
                return back()->with('error', 'Fehler beim Hochladen der Datei.');
            }
        }
        $kontakt->save();

        Mail::to(config('kontaktformular.mailTo'))->send(new Anfrage($kontakt));

        return redirect(route('danke_formular'));
    }

    /**
     * File Download und löschen
     *
     * @param string $hash
     * @param string $name
     * @param integer $id
     * @return void
     */
    public function file_download(string $hash,string $name, int $id){
        $kontakt = Kontaktformular::find($id);
        if($kontakt->filehash != $hash){
            return back()->with('error', 'Fehler beim Download der Datei.');
        }
        if($kontakt->filename != $name){
            return back()->with('error', 'Fehler beim Download der Datei.');
        }


        $path = storage_path('app/uploads/' . $hash . '.zip');
        //Prüfen ob Datei existiert
        if(!file_exists($path)){
            return back()->with('error', 'Datei nicht mehr vorhanden.');
            //return 'Datei nicht mehr vorhanden.';
        }


        //Datei downloaden und löschen
        return response()->download($path,$name .'.zip')->deleteFileAfterSend(true);
    }

    public function danke_formular()
    {
        $active = 'index';
        return view('kontaktformular::danke_formular')->with(compact('active'));
    }
}
