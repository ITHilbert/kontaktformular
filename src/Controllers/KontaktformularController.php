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
        //Honeypot
        if($request->has('website') && !empty($request->website)){
            return back()->with('error', 'Spam detected.');
        }

        $request->validate([
            'Name' => 'required',
            'Email' => 'required|email',
            'Telefon' => 'required',
            'Nachricht' => 'required',
            'datenverarbeitung' => 'required',
            'site' => 'required',
            'Datei' => 'nullable|file|max:10240|mimes:pdf,jpg,png,zip,doc,docx,xls,xlsx',
        ]);

        $kontakt = new Kontaktformular();
        $kontakt->name = $request->Name;
        $kontakt->email = $request->Email;
        $kontakt->telefon = $request->Telefon;
        $kontakt->nachricht = $request->Nachricht;
        $kontakt->datenverarbeitung = $request->datenverarbeitung;
        $kontakt->url = $request->site;

        // Prüfen, ob eine Datei hochgeladen wurde
        if ($request->hasFile('Datei')) {

            // Überprüfen Sie, ob der Upload erfolgreich war
            if ($request->file('Datei')->isValid()) {

                // Originalname der Datei
                $originalName = $request->file('Datei')->getClientOriginalName();
                // Sicherer Dateiname (nur Buchstaben, Zahlen, Bindestriche, Unterstriche)
                $safeName = preg_replace('/[^a-zA-Z0-9\-\_\.]/', '', basename($originalName));
                
                //Dateiendung ermitteln
                $dateityp = $request->Datei->extension();
                
                //originalName ohne Dateiendung (sicher)
                $originalNameOhneEndung = pathinfo($safeName, PATHINFO_FILENAME);

                // Datei speichern
                $path = $request->Datei->store('kontaktformular');
                //Path ohne uploads und Dateiendung
                $path = str_replace('kontaktformular/', '', $path);
                
                //Datei ohne Endung
                $path = str_replace('.' . $request->Datei->extension(), '', $path);

                if($dateityp != 'zip'){
                    //Datei zippen und speichern
                    $zip = new \ZipArchive();
                    $zip->open(storage_path('app/kontaktformular/' . $path . '.zip'), \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
                    $zip->addFile(storage_path('app/kontaktformular/' . $path . '.' . $dateityp), $safeName);
                    $zip->close();

                    //Ungezippte Datei löschen
                    unlink(storage_path('app/kontaktformular/' . $path . '.' . $dateityp));
                }

                $kontakt->file_name = $originalNameOhneEndung;
                $kontakt->file_hash = $path;
                $kontakt->file_type = $dateityp;
                $kontakt->file_size = round($request->Datei->getSize() /(1024*1024),2);
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
        if($kontakt->file_hash != $hash){
            return 'Fehler beim Download der Datei.';
            //return back()->with('error', 'Fehler beim Download der Datei.');
        }
        if($kontakt->file_name != $name){
            return 'Fehler beim Download der Datei.';
            //return back()->with('error', 'Fehler beim Download der Datei.');
        }


        $path = storage_path('app/kontaktformular/' . $hash . '.zip');
        //Prüfen ob Datei existiert
        if(!file_exists($path)){
            //return back()->with('error', 'Datei nicht mehr vorhanden.');
            return 'Datei nicht mehr vorhanden.';
        }

        //Protokollieren wann die Datei heruntergeladen wurde
        $kontakt->file_downloaded_at = date('Y-m-d H:i:s');
        //$kontakt->file_deleted_at = date('Y-m-d H:i:s');
        $kontakt->update();

        //Datei downloaden und löschen
        return response()->download($path,$name .'.zip')->deleteFileAfterSend(false);
    }


    /**
     * Danke Formular öffnen
     *
     * @return void
     */
    public function danke_formular()
    {
        $active = 'index';
        return view('kontaktformular::danke_formular')->with(compact('active'));
    }
}
