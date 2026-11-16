<?php

declare(strict_types=1);

namespace ITHilbert\Kontaktformular\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Mail;
use ITHilbert\Kontaktformular\Anhangablage;
use ITHilbert\Kontaktformular\Exceptions\AnfrageMailNichtVersendet;
use ITHilbert\Kontaktformular\Mail\Anfrage;
use ITHilbert\Kontaktformular\Middleware\HerkunftErfassen;
use ITHilbert\Kontaktformular\Models\Kontaktformular;
use ITHilbert\Kontaktformular\ValueObjects\Herkunft;
use ITHilbert\Kontaktformular\ValueObjects\SiteAdresse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

final class KontaktformularController extends Controller
{
    public function anfrage(Request $request): RedirectResponse
    {
        if ($request->filled('fax')) {
            return redirect()->route('danke_bot_formular');
        }

        $nachrichtPflicht = (bool) (config('kontaktformular.nachricht_pflicht') ?? true);
        $feldWebsite = (bool) config('kontaktformular.feld_website');

        $nachricht = trim((string) $request->input('Nachricht', ''));
        if (($nachrichtPflicht || $nachricht !== '') && (strlen($nachricht) < 10 || strpos($nachricht, ' ') === false)) {
            return redirect()->route('danke_bot_formular');
        }

        $regeln = [
            'Name' => 'required|string|max:255',
            'Email' => 'required|email|max:255',
            'Telefon' => 'nullable|string|max:50',
            'Nachricht' => $nachrichtPflicht ? 'required|string' : 'nullable|string',
            'datenverarbeitung' => 'required|accepted',
            'site' => 'required|string|max:2048',
            'Datei' => 'nullable|file|max:10240|mimes:pdf,jpg,png,zip,doc,docx,xls,xlsx',
        ];
        if ($feldWebsite) {
            $regeln['Website'] = 'nullable|string|max:2048';
        }
        $validated = $request->validate($regeln);

        $kontakt = new Kontaktformular;
        $kontakt->name = $validated['Name'];
        $kontakt->email = $validated['Email'];
        $kontakt->telefon = $validated['Telefon'] ?? null;
        $kontakt->nachricht = $nachricht;
        if ($feldWebsite) {
            $website = trim((string) ($validated['Website'] ?? ''));
            $kontakt->website = $website === '' ? null : str_replace(["\r", "\n"], ' ', $website);
        }
        $kontakt->datenverarbeitung = (bool) $validated['datenverarbeitung'];
        $kontakt->url = SiteAdresse::aus($validated['site'])->ohneKlickIds();

        $herkunft = $this->herkunftAusSession($request);
        if ($herkunft !== null) {
            $kontakt->herkunft = $herkunft;
        }

        if ($request->hasFile('Datei') && $request->file('Datei')->isValid()) {
            $this->attachUploadedFile($request, $kontakt);
        }

        $kontakt->save();

        try {
            Mail::to(config('kontaktformular.mailTo'))->send(new Anfrage($kontakt));
        } catch (Throwable $e) {
            // Die Anfrage ist gespeichert; der Besucher soll sie nicht erneut absenden müssen.
            report(AnfrageMailNichtVersendet::fuer($kontakt, $e));
        }

        return redirect()->route('danke_formular');
    }

    /**
     * Frisches CSRF-Token der aktuellen Sitzung (wird vor dem Absenden von der Vue-Komponente geholt).
     */
    public function csrf_token(): JsonResponse
    {
        return response()
            ->json(['token' => csrf_token()])
            ->header('Cache-Control', 'no-store, private');
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

        $ablage = new Anhangablage;
        if (! $ablage->existiert($hash)) {
            abort(410, 'Datei nicht mehr vorhanden.');
        }

        $kontakt->file_downloaded_at = now();
        $kontakt->save();

        return response()->download($ablage->lokalerPfad($hash), $name.'.zip')->deleteFileAfterSend(false);
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

    /**
     * @return array<string, string>|null
     */
    private function herkunftAusSession(Request $request): ?array
    {
        if (! config('kontaktformular.herkunft_erfassen')) {
            return null;
        }

        $daten = $request->hasSession() ? $request->session()->get(HerkunftErfassen::SESSION_KEY) : null;
        if (! is_array($daten)) {
            return null;
        }

        $herkunft = Herkunft::fromArray($daten)->toArray();

        return $herkunft === [] ? null : $herkunft;
    }

    private function attachUploadedFile(Request $request, Kontaktformular $kontakt): void
    {
        $upload = $request->file('Datei');
        $originalName = $upload->getClientOriginalName();
        $safeName = preg_replace('/[^a-zA-Z0-9\-\_\.]/', '', basename($originalName));
        $dateityp = $upload->extension();
        $nameOhneExt = pathinfo($safeName, PATHINFO_FILENAME);

        $hash = (new Anhangablage)->speichern($upload, $safeName, $dateityp);

        $kontakt->file_name = $nameOhneExt;
        $kontakt->file_hash = $hash;
        $kontakt->file_type = $dateityp;
        $kontakt->file_size = round($upload->getSize() / (1024 * 1024), 2);
    }
}
