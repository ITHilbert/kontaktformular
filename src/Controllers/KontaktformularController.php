<?php

declare(strict_types=1);

namespace ITHilbert\Kontaktformular\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use ITHilbert\Kontaktformular\Mail\Anfrage;
use ITHilbert\Kontaktformular\Models\Kontaktformular;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

final class KontaktformularController extends Controller
{
    public function anfrage(Request $request): RedirectResponse
    {
        if ($request->filled('fax')) {
            return redirect()->route('danke_bot_formular');
        }

        $nachricht = trim((string) $request->input('Nachricht', ''));
        if (strlen($nachricht) < 10 || strpos($nachricht, ' ') === false) {
            return redirect()->route('danke_bot_formular');
        }

        $validated = $request->validate([
            'Name'              => 'required|string|max:255',
            'Email'             => 'required|email|max:255',
            'Telefon'           => 'nullable|string|max:50',
            'Nachricht'         => 'required|string',
            'datenverarbeitung' => 'required|accepted',
            'site'              => 'required|string|max:2048',
            'Datei'             => 'nullable|file|max:10240|mimes:pdf,jpg,png,zip,doc,docx,xls,xlsx',
        ]);

        $kontakt = new Kontaktformular();
        $kontakt->name              = $validated['Name'];
        $kontakt->email             = $validated['Email'];
        $kontakt->telefon           = $validated['Telefon'] ?? null;
        $kontakt->nachricht         = $validated['Nachricht'];
        $kontakt->datenverarbeitung = (bool) $validated['datenverarbeitung'];
        $kontakt->url               = $validated['site'];

        if ($request->hasFile('Datei') && $request->file('Datei')->isValid()) {
            $this->attachUploadedFile($request, $kontakt);
        }

        $kontakt->save();

        Mail::to(config('kontaktformular.mailTo'))->send(new Anfrage($kontakt));

        return redirect()->route('danke_formular');
    }

    /**
     * File-Download mit Hash- und Namens-Verifikation.
     */
    public function file_download(string $hash, string $name, int $id): BinaryFileResponse|Response
    {
        $kontakt = Kontaktformular::find($id);

        if ($kontakt === null || $kontakt->file_hash !== $hash || $kontakt->file_name !== $name) {
            abort(404, 'Datei nicht gefunden.');
        }

        $path = storage_path('app/kontaktformular/' . $hash . '.zip');
        if (! file_exists($path)) {
            abort(410, 'Datei nicht mehr vorhanden.');
        }

        $kontakt->file_downloaded_at = now();
        $kontakt->save();

        return response()->download($path, $name . '.zip')->deleteFileAfterSend(false);
    }

    public function danke_formular()
    {
        $active = 'index';
        return view('kontaktformular::danke_formular')->with(compact('active'));
    }

    /**
     * Bot-Danke-Formular (als Erfolg getarnt, löst keine Analytics aus).
     */
    public function danke_bot_formular()
    {
        $active = 'index';
        return view('kontaktformular::danke_bot_formular')->with(compact('active'));
    }

    private function attachUploadedFile(Request $request, Kontaktformular $kontakt): void
    {
        $upload       = $request->file('Datei');
        $originalName = $upload->getClientOriginalName();
        $safeName     = preg_replace('/[^a-zA-Z0-9\-\_\.]/', '', basename($originalName));
        $dateityp     = $upload->extension();
        $nameOhneExt  = pathinfo($safeName, PATHINFO_FILENAME);

        $storedPath = $upload->store('kontaktformular');
        $hash       = str_replace('kontaktformular/', '', $storedPath);
        $hash       = str_replace('.' . $dateityp, '', $hash);

        if ($dateityp !== 'zip') {
            $sourcePath = storage_path('app/kontaktformular/' . $hash . '.' . $dateityp);
            $zipPath    = storage_path('app/kontaktformular/' . $hash . '.zip');

            $zip = new ZipArchive();
            $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
            $zip->addFile($sourcePath, $safeName);
            $zip->close();

            unlink($sourcePath);
        }

        $kontakt->file_name = $nameOhneExt;
        $kontakt->file_hash = $hash;
        $kontakt->file_type = $dateityp;
        $kontakt->file_size = round($upload->getSize() / (1024 * 1024), 2);
    }
}
