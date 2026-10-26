<?php

declare(strict_types=1);

namespace ITHilbert\Kontaktformular\Tests\Feature;

use Illuminate\Routing\Router;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use ITHilbert\Kontaktformular\Mail\Anfrage;
use ITHilbert\Kontaktformular\Models\Kontaktformular;
use ITHilbert\Kontaktformular\Tests\TestCase;

final class AnfrageHerkunftTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('kontaktformular.mailFrom', 'from@example.com');
        config()->set('kontaktformular.mailTo', 'to@example.com');
        config()->set('kontaktformular.subject', 'Test-Anfrage');
        config()->set('kontaktformular.mailBcc', null);
        config()->set('kontaktformular.fileDownloadUrl', 'http://localhost/kontaktformular/file/');

        // Tabelle kommt aus den Paket-Migrationen, nicht aus dem Test.
        Artisan::call('migrate', ['--force' => true]);

        Mail::fake();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * @param  Router  $router
     */
    protected function defineRoutes($router): void
    {
        $router->middleware('web')->group(function () use ($router): void {
            foreach (['/excel-programmierer', '/preise', '/startseite'] as $pfad) {
                $router->get($pfad, fn () => response()->json(['session' => request()->hasSession()]));
            }
        });

        $router->middleware('api')->get('/api-ohne-session', fn () => response()->json(['session' => request()->hasSession()]));
    }

    // ------------------------------------------------------------------
    // Hilfen
    // ------------------------------------------------------------------

    /**
     * @return array<string, string>
     */
    private function gueltigeAnfrage(): array
    {
        return [
            'Name' => 'Max Mustermann',
            'Email' => 'max@example.com',
            'Telefon' => '+49 1234 567890',
            'Nachricht' => 'Das ist eine valide Testnachricht mit genug Inhalt.',
            'datenverarbeitung' => '1',
            'site' => 'http://localhost',
        ];
    }

    /**
     * Beginnt eine neue, leere Session (Session-Treiber verwirft seinen Zustand).
     */
    private function neueSession(): void
    {
        $this->app->make('session')->forgetDrivers();
    }

    private function sessionTreiber(string $treiber): void
    {
        config()->set('session.driver', $treiber);
        $this->neueSession();
    }

    /**
     * @param  array<string, string>  $header
     */
    private function besuche(string $url, array $header = []): TestResponse
    {
        return $this->get($url, $header);
    }

    /**
     * Schickt die Anfrage in der aktuellen Session und liefert die versendete Mail.
     *
     * @param  array<string, string>  $header
     */
    private function sendeAnfrage(array $header = [], string $query = ''): Anfrage
    {
        $vorher = Mail::sent(Anfrage::class)->count();

        $this->post(route('anfrage').$query, $this->gueltigeAnfrage(), $header)
            ->assertRedirect(route('danke_formular'));

        $gesendet = Mail::sent(Anfrage::class);
        $this->assertCount($vorher + 1, $gesendet, 'Anfrage wurde nicht verschickt');

        return $gesendet->last();
    }

    /**
     * @return array{html: string, text: string}
     */
    private function inhalt(Anfrage $mail): array
    {
        return [
            'html' => $mail->render(),
            'text' => view('kontaktformular::mail.anfrage_plain', $mail->buildViewData())->render(),
        ];
    }

    /**
     * @return list<string>
     */
    private function zeilen(string $text): array
    {
        return array_values(array_map('rtrim', preg_split('/\R/', $text)));
    }

    /**
     * Wert hinter dem Präfix (z. B. "Herkunft:") im Text-Teil, null wenn die Zeile fehlt.
     */
    private function textWert(string $text, string $praefix): ?string
    {
        foreach ($this->zeilen($text) as $zeile) {
            if (str_starts_with($zeile, $praefix)) {
                return trim(substr($zeile, strlen($praefix)));
            }
        }

        return null;
    }

    /**
     * Tabellenzeilen des HTML-Teils in Reihenfolge: Liste aus [Beschriftung, Wert].
     *
     * @return list<array{0: string, 1: string}>
     */
    private function htmlZeilen(string $html): array
    {
        preg_match_all('#<tr\b[^>]*>(.*?)</tr>#s', $html, $zeilen);

        $ergebnis = [];
        foreach ($zeilen[1] as $zeile) {
            preg_match_all('#<td\b[^>]*>(.*?)</td>#s', $zeile, $zellen);
            if (count($zellen[1]) >= 2) {
                $ergebnis[] = [
                    trim(html_entity_decode(strip_tags($zellen[1][0]))),
                    trim(html_entity_decode(strip_tags($zellen[1][1]))),
                ];
            }
        }

        return $ergebnis;
    }

    private function htmlWert(string $html, string $beschriftung): ?string
    {
        foreach ($this->htmlZeilen($html) as [$label, $wert]) {
            if ($label === $beschriftung) {
                return $wert;
            }
        }

        return null;
    }

    /**
     * Prüft Text- und HTML-Teil auf die vier Herkunftszeilen.
     */
    private function assertHerkunftZeilen(Anfrage $mail, string $herkunft, string $einstieg, string $referrer, string $klickId): void
    {
        $inhalt = $this->inhalt($mail);
        $erwartet = ['Herkunft:' => $herkunft, 'Einstieg:' => $einstieg, 'Referrer:' => $referrer, 'Klick-ID:' => $klickId];

        foreach ($erwartet as $label => $wert) {
            $this->assertSame($wert, $this->textWert($inhalt['text'], $label), "Text-Teil: {$label}");
            $this->assertSame($wert, $this->htmlWert($inhalt['html'], $label), "HTML-Teil: {$label}");
        }
    }

    private function herkunftSpalteVorhanden(): void
    {
        $this->assertTrue(Schema::hasColumn('kontaktformular', 'herkunft'), 'Spalte herkunft fehlt');
    }

    private function herkunftRoh(): ?string
    {
        $this->herkunftSpalteVorhanden();
        $wert = DB::table('kontaktformular')->orderByDesc('id')->value('herkunft');

        return $wert === null ? null : (string) $wert;
    }

    /**
     * Alle nicht leeren Werte des JSON der Spalte (flach).
     *
     * @return list<string>
     */
    private function herkunftWerte(): array
    {
        $roh = $this->herkunftRoh();
        $this->assertNotNull($roh, 'herkunft ist null');
        $daten = json_decode($roh, true);
        $this->assertIsArray($daten, 'herkunft ist kein JSON-Objekt/-Array');

        $werte = [];
        array_walk_recursive($daten, function ($wert) use (&$werte): void {
            if ($wert !== null && $wert !== '') {
                $werte[] = (string) $wert;
            }
        });

        return $werte;
    }

    // ------------------------------------------------------------------
    // Abnahme-Szenarien (Dashboard-Task)
    // ------------------------------------------------------------------

    // AK1, AK2, AK4, AK5
    public function test_szenario_a_gclid_ueber_zweite_seite_bis_zum_formular_ergibt_google_ads(): void
    {
        $this->besuche('/excel-programmierer?gclid=x', ['Referer' => 'https://google.com/'])->assertOk();
        $this->besuche('/preise')->assertOk();

        $mail = $this->sendeAnfrage();

        $this->assertSame('google_ads', $this->textWert($this->inhalt($mail)['text'], 'Herkunft:'));
        $this->assertHerkunftZeilen($mail, 'google_ads', '/excel-programmierer', 'google.com', 'gclid');
    }

    // AK5
    public function test_szenario_b_ohne_parameter_mit_referrer_ergibt_organisch_sonstige(): void
    {
        $this->besuche('/startseite', ['Referer' => 'https://duckduckgo.com/?q=excel'])->assertOk();

        $mail = $this->sendeAnfrage();

        $this->assertSame('organisch/sonstige', $this->textWert($this->inhalt($mail)['text'], 'Herkunft:'));
    }

    // AK5
    public function test_szenario_c_ohne_parameter_und_ohne_referrer_ergibt_direkt(): void
    {
        $this->besuche('/startseite')->assertOk();

        $mail = $this->sendeAnfrage();

        $this->assertSame('direkt', $this->textWert($this->inhalt($mail)['text'], 'Herkunft:'));
    }

    // ------------------------------------------------------------------
    // AK1 — Erfassen beim ersten Request
    // ------------------------------------------------------------------

    // AK1
    public function test_erster_request_speichert_alle_sieben_parameter_einstieg_und_referrer_host(): void
    {
        $url = '/excel-programmierer?gclid=G-1&gbraid=B-2&wbraid=W-3&utm_source=quelle-4&utm_medium=medium-5&utm_campaign=kampagne-6&utm_term=begriff-7';

        // Aufbau greift: Route ist erreichbar und hat eine Session
        $this->besuche($url, ['Referer' => 'https://google.com/search?q=geheim'])
            ->assertOk()
            ->assertJson(['session' => true]);

        $this->sendeAnfrage();

        $this->assertEqualsCanonicalizing(
            ['G-1', 'B-2', 'W-3', 'quelle-4', 'medium-5', 'kampagne-6', 'begriff-7', '/excel-programmierer', 'google.com'],
            $this->herkunftWerte()
        );
    }

    // AK1
    public function test_nur_vorhandene_parameter_werden_gespeichert(): void
    {
        $this->besuche('/excel-programmierer?gclid=NUR-DIE-EINE')->assertOk();

        $this->sendeAnfrage();

        $this->assertEqualsCanonicalizing(['NUR-DIE-EINE', '/excel-programmierer'], $this->herkunftWerte());
    }

    // AK1
    public function test_einstieg_ist_der_pfad_ohne_query(): void
    {
        $this->besuche('/excel-programmierer?utm_source=x&foo=bar')->assertOk();

        $mail = $this->sendeAnfrage();

        $this->assertSame('/excel-programmierer', $this->textWert($this->inhalt($mail)['text'], 'Einstieg:'));
        foreach ($this->herkunftWerte() as $wert) {
            $this->assertStringNotContainsString('?', $wert);
            $this->assertStringNotContainsString('foo', $wert);
        }
    }

    // AK1
    public function test_vom_referer_wird_nur_der_host_gespeichert(): void
    {
        $this->besuche('/excel-programmierer', ['Referer' => 'https://google.com/search?q=geheim#anker'])->assertOk();

        $mail = $this->sendeAnfrage();

        $this->assertSame('google.com', $this->textWert($this->inhalt($mail)['text'], 'Referrer:'));
        $werte = $this->herkunftWerte();
        $this->assertContains('google.com', $werte);
        foreach ($werte as $wert) {
            $this->assertStringNotContainsString('geheim', $wert);
            $this->assertStringNotContainsString('search', $wert);
            $this->assertStringNotContainsString('https', $wert);
        }
    }

    // ------------------------------------------------------------------
    // AK2 — First Touch
    // ------------------------------------------------------------------

    // AK2
    public function test_spaetere_requests_mit_anderen_parametern_und_referrer_aendern_nichts(): void
    {
        $this->besuche('/excel-programmierer?gclid=ERSTE&utm_source=google', ['Referer' => 'https://google.com/'])->assertOk();
        $this->besuche('/preise?gbraid=ZWEITE&utm_campaign=neu&utm_medium=cpc', ['Referer' => 'https://bing.com/'])->assertOk();

        $mail = $this->sendeAnfrage(['Referer' => 'https://yahoo.com/'], '?wbraid=DRITTE');

        // Gegenprobe: Session greift, erste Werte sind da
        $this->assertContains('ERSTE', $this->herkunftWerte());

        $this->assertEqualsCanonicalizing(['ERSTE', 'google', '/excel-programmierer', 'google.com'], $this->herkunftWerte());
        $this->assertHerkunftZeilen($mail, 'google_ads', '/excel-programmierer', 'google.com', 'gclid');
    }

    // AK2
    public function test_gespeicherte_direkt_herkunft_wird_von_spaeterem_gclid_nicht_ueberschrieben(): void
    {
        // Gegenprobe: dieselbe Folge nur mit dem gclid-Request in frischer Session ergibt google_ads
        $this->besuche('/preise?gclid=SPAET')->assertOk();
        $gegenprobe = $this->sendeAnfrage();
        $this->assertSame('google_ads', $this->textWert($this->inhalt($gegenprobe)['text'], 'Herkunft:'));

        $this->neueSession();
        $this->besuche('/startseite')->assertOk();
        $this->besuche('/preise?gclid=SPAET', ['Referer' => 'https://google.com/'])->assertOk();

        $mail = $this->sendeAnfrage();

        $this->assertHerkunftZeilen($mail, 'direkt', '/startseite', '-', '-');
        $this->assertNotContains('SPAET', $this->herkunftWerte());
    }

    // ------------------------------------------------------------------
    // AK3 — Speichern mit der Anfrage
    // ------------------------------------------------------------------

    // AK3
    public function test_migration_legt_nullable_json_spalte_herkunft_an(): void
    {
        $this->herkunftSpalteVorhanden();

        $spalte = collect(Schema::getColumns('kontaktformular'))->firstWhere('name', 'herkunft');
        $this->assertNotNull($spalte);
        $this->assertTrue($spalte['nullable']);
    }

    // AK3
    public function test_gueltige_anfrage_speichert_herkunft_der_session_als_json(): void
    {
        $this->besuche('/excel-programmierer?gclid=TESTGCLID123&utm_source=google')->assertOk();

        $this->sendeAnfrage();

        $this->assertDatabaseCount('kontaktformular', 1);
        $werte = $this->herkunftWerte();
        $this->assertContains('TESTGCLID123', $werte);
        $this->assertContains('google', $werte);
    }

    // AK3
    public function test_ohne_gespeicherte_herkunft_bleibt_spalte_null_und_anfrage_wird_gespeichert_und_verschickt(): void
    {
        // Keine Herkunft in der Session: Treiber "cookie" speichert nichts (Randfall 9)
        $this->sessionTreiber('cookie');
        $this->besuche('/excel-programmierer?gclid=NICHT-GESPEICHERT')->assertOk();

        $mail = $this->sendeAnfrage();

        $this->assertDatabaseCount('kontaktformular', 1);
        $this->assertDatabaseHas('kontaktformular', ['email' => 'max@example.com']);
        $this->assertNull($this->herkunftRoh());
        $this->assertCount(1, Mail::sent(Anfrage::class));
        $this->assertNotSame('', $mail->render());
    }

    // ------------------------------------------------------------------
    // AK4 — Zeilen in der Mail
    // ------------------------------------------------------------------

    // AK4
    public function test_html_und_text_teil_enthalten_die_vier_zeilen(): void
    {
        $this->besuche('/excel-programmierer?gclid=abc', ['Referer' => 'https://google.com/'])->assertOk();

        $mail = $this->sendeAnfrage();

        $this->assertHerkunftZeilen($mail, 'google_ads', '/excel-programmierer', 'google.com', 'gclid');
    }

    // AK4
    public function test_zeilen_stehen_direkt_nach_site_im_text_teil_in_fester_reihenfolge(): void
    {
        $this->besuche('/excel-programmierer?gclid=abc', ['Referer' => 'https://google.com/'])->assertOk();
        $text = $this->inhalt($this->sendeAnfrage())['text'];

        $zeilen = $this->zeilen($text);
        $index = null;
        foreach ($zeilen as $i => $zeile) {
            if (str_starts_with($zeile, 'Site:')) {
                $index = $i;
            }
        }
        $this->assertNotNull($index, 'Zeile Site: fehlt');

        $this->assertStringStartsWith('Herkunft:', $zeilen[$index + 1] ?? '');
        $this->assertStringStartsWith('Einstieg:', $zeilen[$index + 2] ?? '');
        $this->assertStringStartsWith('Referrer:', $zeilen[$index + 3] ?? '');
        $this->assertStringStartsWith('Klick-ID:', $zeilen[$index + 4] ?? '');
    }

    // AK4
    public function test_zeilen_stehen_direkt_nach_site_im_html_teil_als_tabellenzeilen(): void
    {
        $this->besuche('/excel-programmierer?gclid=abc', ['Referer' => 'https://google.com/'])->assertOk();
        $html = $this->inhalt($this->sendeAnfrage())['html'];

        $labels = array_column($this->htmlZeilen($html), 0);
        $index = array_search('Site:', $labels, true);
        $this->assertNotFalse($index, 'Tabellenzeile Site: fehlt');

        $this->assertSame(
            ['Herkunft:', 'Einstieg:', 'Referrer:', 'Klick-ID:'],
            array_slice($labels, $index + 1, 4)
        );
    }

    // AK4
    public function test_fehlender_einstieg_und_referrer_zeigen_einen_strich(): void
    {
        // Alter Datensatz ohne Herkunft
        $kontakt = new Kontaktformular([
            'url' => 'http://localhost',
            'name' => 'Alt Kunde',
            'email' => 'alt@example.com',
            'nachricht' => 'Eine alte Anfrage mit Text.',
            'datenverarbeitung' => true,
        ]);
        $kontakt->save();

        // Gegenprobe: dieselbe Mail mit Herkunft zeigt echte Werte (Session-Aufbau)
        $this->besuche('/excel-programmierer?gclid=abc', ['Referer' => 'https://google.com/'])->assertOk();
        $mitHerkunft = $this->sendeAnfrage();
        $this->assertSame('/excel-programmierer', $this->textWert($this->inhalt($mitHerkunft)['text'], 'Einstieg:'));

        $this->assertHerkunftZeilen(new Anfrage($kontakt->fresh()), 'direkt', '-', '-', '-');
    }

    // ------------------------------------------------------------------
    // AK5 — Einordnung
    // ------------------------------------------------------------------

    // AK5, AK6
    public function test_jede_klick_id_ergibt_google_ads_auch_mit_referrer(): void
    {
        foreach (['gclid', 'gbraid', 'wbraid'] as $name) {
            $this->neueSession();
            $this->besuche("/startseite?{$name}=wert", ['Referer' => 'https://bing.com/'])->assertOk();

            $mail = $this->sendeAnfrage();

            $this->assertHerkunftZeilen($mail, 'google_ads', '/startseite', 'bing.com', $name);
        }
    }

    // AK5
    public function test_utm_google_cpc_ergibt_google_ads(): void
    {
        $this->besuche('/startseite?utm_source=google&utm_medium=cpc', ['Referer' => 'https://bing.com/'])->assertOk();

        $mail = $this->sendeAnfrage();

        $this->assertHerkunftZeilen($mail, 'google_ads', '/startseite', 'bing.com', '-');
    }

    // AK5
    public function test_utm_source_google_allein_oder_cpc_allein_ist_kein_google_ads(): void
    {
        // Gegenprobe: beide zusammen ergeben google_ads
        $this->besuche('/startseite?utm_source=google&utm_medium=cpc')->assertOk();
        $this->assertSame('google_ads', $this->textWert($this->inhalt($this->sendeAnfrage())['text'], 'Herkunft:'));

        foreach (['utm_source=google', 'utm_medium=cpc', 'utm_source=google&utm_medium=email', 'utm_source=bing&utm_medium=cpc'] as $query) {
            $this->neueSession();
            $this->besuche('/startseite?'.$query)->assertOk();

            $this->assertSame('direkt', $this->textWert($this->inhalt($this->sendeAnfrage())['text'], 'Herkunft:'), $query);
        }
    }

    // AK5
    public function test_referrer_ohne_klick_id_ergibt_organisch_sonstige(): void
    {
        $this->besuche('/startseite', ['Referer' => 'https://duckduckgo.com/'])->assertOk();

        $mail = $this->sendeAnfrage();

        $this->assertHerkunftZeilen($mail, 'organisch/sonstige', '/startseite', 'duckduckgo.com', '-');
    }

    // AK5
    public function test_ohne_klick_id_und_ohne_referrer_ergibt_direkt(): void
    {
        $this->besuche('/startseite?utm_campaign=sommer')->assertOk();

        $mail = $this->sendeAnfrage();

        $this->assertHerkunftZeilen($mail, 'direkt', '/startseite', '-', '-');
    }

    // ------------------------------------------------------------------
    // AK6 — Klick-ID ohne Wert
    // ------------------------------------------------------------------

    // AK6
    public function test_klick_id_nennt_nur_den_namen(): void
    {
        $this->besuche('/startseite?wbraid=TESTWB999')->assertOk();

        $mail = $this->sendeAnfrage();

        $this->assertSame('wbraid', $this->textWert($this->inhalt($mail)['text'], 'Klick-ID:'));
    }

    // AK6
    public function test_ohne_klick_id_steht_ein_strich(): void
    {
        $this->besuche('/startseite?utm_source=google&utm_medium=cpc')->assertOk();

        $mail = $this->sendeAnfrage();

        $this->assertHerkunftZeilen($mail, 'google_ads', '/startseite', '-', '-');
    }

    // AK6
    public function test_wert_der_klick_id_steht_nie_im_html_oder_text_teil(): void
    {
        $this->besuche('/excel-programmierer?gclid=TESTGCLID123&gbraid=TESTGBRAID456&wbraid=TESTWBRAID789')->assertOk();

        $mail = $this->sendeAnfrage();
        $inhalt = $this->inhalt($mail);

        // Gegenprobe: Wert wurde erfasst und die Klick-ID-Zeile wird gerendert
        $this->assertContains('TESTGCLID123', $this->herkunftWerte());
        $this->assertSame('gclid', $this->textWert($inhalt['text'], 'Klick-ID:'));

        foreach (['TESTGCLID123', 'TESTGBRAID456', 'TESTWBRAID789'] as $wert) {
            $this->assertStringNotContainsString($wert, $inhalt['html']);
            $this->assertStringNotContainsString($wert, $inhalt['text']);
        }
    }

    // ------------------------------------------------------------------
    // AK7 — Kein neuer Cookie
    // ------------------------------------------------------------------

    /**
     * @return list<string>
     */
    private function cookieNamen(TestResponse $response): array
    {
        $namen = array_map(static fn ($cookie): string => $cookie->getName(), $response->headers->getCookies());
        sort($namen);

        return $namen;
    }

    // AK7
    public function test_parameter_fuegen_der_response_keine_cookies_hinzu(): void
    {
        $verzeichnis = storage_path('framework/sessions');
        if (! is_dir($verzeichnis)) {
            mkdir($verzeichnis, 0777, true);
        }
        config()->set('session.files', $verzeichnis);
        $this->sessionTreiber('file');

        $ohne = $this->cookieNamen($this->besuche('/excel-programmierer')->assertOk());

        // Gegenprobe: ohne Parameter wird überhaupt ein Session-Cookie gesetzt
        $this->assertContains(config('session.cookie'), $ohne);

        $this->neueSession();
        $mit = $this->cookieNamen($this->besuche('/excel-programmierer?gclid=abc&utm_source=google')->assertOk());

        $this->assertSame($ohne, $mit);
    }

    /**
     * @return list<string>
     */
    private function srcDateienMit(string $suchbegriff): array
    {
        $treffer = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(dirname(__DIR__, 2).'/src', \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $datei) {
            if ($datei->isFile() && str_contains((string) file_get_contents($datei->getPathname()), $suchbegriff)) {
                $treffer[] = $datei->getPathname();
            }
        }

        return $treffer;
    }

    // AK7
    public function test_paket_nutzt_weder_local_storage_noch_session_storage(): void
    {
        // Gegenprobe: die Suche findet Dateien
        $this->assertNotEmpty($this->srcDateienMit('Kontaktformular'));

        $this->assertSame([], $this->srcDateienMit('localStorage'));
        $this->assertSame([], $this->srcDateienMit('sessionStorage'));
    }

    // ------------------------------------------------------------------
    // AK8 — Bestehende Zeilen unverändert
    // ------------------------------------------------------------------

    // AK8
    public function test_nummer_datum_und_site_im_text_teil_bleiben_unveraendert(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 26, 12, 0, 0));
        $this->besuche('/excel-programmierer?gclid=abc', ['Referer' => 'https://google.com/'])->assertOk();

        $text = $this->inhalt($this->sendeAnfrage())['text'];

        // Gegenprobe: die neuen Zeilen sind vorhanden (Aufbau greift)
        $this->assertSame('google_ads', $this->textWert($text, 'Herkunft:'));

        $zeilen = $this->zeilen($text);
        $erwartet = [
            'Kontaktformular',
            'Nummer: 1',
            'Name: Max Mustermann',
            'E-Mail: max@example.com',
            'Telefon: +49 1234 567890',
            'Mitteilung: Das ist eine valide Testnachricht mit genug Inhalt.',
            'Datenverarbeitung: ja',
            'Datum: 2026-10-26 12:00:00',
            'Site: http://localhost',
        ];
        $this->assertSame($erwartet, array_slice($zeilen, 0, count($erwartet)));
    }

    // ------------------------------------------------------------------
    // Randfälle
    // ------------------------------------------------------------------

    // Randfall 1
    public function test_mehrere_klick_ids_nennen_die_erste_in_reihenfolge_gclid_gbraid_wbraid(): void
    {
        $this->besuche('/startseite?wbraid=W&gbraid=B&gclid=G')->assertOk();
        $this->assertSame('gclid', $this->textWert($this->inhalt($this->sendeAnfrage())['text'], 'Klick-ID:'));

        $this->neueSession();
        $this->besuche('/startseite?wbraid=W&gbraid=B')->assertOk();
        $this->assertSame('gbraid', $this->textWert($this->inhalt($this->sendeAnfrage())['text'], 'Klick-ID:'));
    }

    // Randfall 2
    public function test_utm_source_und_medium_in_anderer_schreibweise_zaehlen_wie_google_cpc(): void
    {
        $this->besuche('/startseite?utm_source=Google&utm_medium=CPC')->assertOk();

        $mail = $this->sendeAnfrage();

        $this->assertHerkunftZeilen($mail, 'google_ads', '/startseite', '-', '-');
    }

    // Randfall 3
    public function test_referrer_mit_eigenem_host_gilt_als_kein_referrer(): void
    {
        // Gegenprobe: fremder Host ergibt organisch/sonstige
        $this->besuche('/startseite', ['Referer' => 'https://fremd.example.org/seite'])->assertOk();
        $this->assertHerkunftZeilen($this->sendeAnfrage(), 'organisch/sonstige', '/startseite', 'fremd.example.org', '-');

        $this->neueSession();
        $this->besuche('/startseite', ['Referer' => 'http://localhost/preise'])->assertOk();

        $this->assertHerkunftZeilen($this->sendeAnfrage(), 'direkt', '/startseite', '-', '-');
        $this->assertNotContains('localhost', $this->herkunftWerte());
    }

    // Randfall 4
    public function test_referer_ohne_gueltigen_host_gilt_als_kein_referrer_ohne_fehler(): void
    {
        // Gegenprobe: gültiger Host wird übernommen
        $this->besuche('/startseite', ['Referer' => 'https://fremd.example.org/'])->assertOk();
        $this->assertSame('fremd.example.org', $this->textWert($this->inhalt($this->sendeAnfrage())['text'], 'Referrer:'));

        foreach (['müll ohne schema', '://', 'http:///pfad', 'android-app:'] as $referer) {
            $this->neueSession();
            $this->besuche('/startseite', ['Referer' => $referer])->assertOk();

            $this->assertHerkunftZeilen($this->sendeAnfrage(), 'direkt', '/startseite', '-', '-');
        }
    }

    // Randfall 5
    public function test_array_parameter_wird_ignoriert(): void
    {
        // Gegenprobe: gleiche Anfrage mit String-Parameter wird erfasst
        $this->besuche('/startseite?gclid=ALS-STRING')->assertOk();
        $this->assertSame('gclid', $this->textWert($this->inhalt($this->sendeAnfrage())['text'], 'Klick-ID:'));

        $this->neueSession();
        $this->besuche('/startseite?gclid[]=ALS-ARRAY&utm_source=google&utm_medium=cpc')->assertOk();

        $mail = $this->sendeAnfrage();

        $this->assertHerkunftZeilen($mail, 'google_ads', '/startseite', '-', '-');
        $this->assertNotContains('ALS-ARRAY', $this->herkunftWerte());
        $this->assertContains('google', $this->herkunftWerte());
    }

    // Randfall 5
    public function test_leerer_parameter_wird_ignoriert(): void
    {
        $this->besuche('/startseite?gclid=&wbraid=NUR-WBRAID&utm_source=')->assertOk();

        $mail = $this->sendeAnfrage();

        $this->assertHerkunftZeilen($mail, 'google_ads', '/startseite', '-', 'wbraid');
        $this->assertEqualsCanonicalizing(['NUR-WBRAID', '/startseite'], $this->herkunftWerte());
    }

    // Randfall 5
    public function test_nur_leerer_parameter_ergibt_direkt_ohne_klick_id(): void
    {
        $this->besuche('/startseite?gclid=')->assertOk();

        $this->assertHerkunftZeilen($this->sendeAnfrage(), 'direkt', '/startseite', '-', '-');
    }

    // Randfall 5
    public function test_laengere_werte_als_255_zeichen_werden_auf_255_gekuerzt(): void
    {
        $this->besuche('/startseite?utm_term='.str_repeat('t', 300).'&utm_campaign='.str_repeat('c', 255))->assertOk();

        $this->sendeAnfrage();

        $werte = $this->herkunftWerte();
        // Gegenprobe: Wert genau an der Grenze bleibt vollständig
        $this->assertContains(str_repeat('c', 255), $werte);
        $this->assertContains(str_repeat('t', 255), $werte);
        $this->assertNotContains(str_repeat('t', 300), $werte);
        foreach ($werte as $wert) {
            $this->assertLessThanOrEqual(255, mb_strlen($wert));
        }
    }

    // Randfall 6
    public function test_erster_request_post_anfrage_erfasst_herkunft_aus_diesem_request(): void
    {
        $mail = $this->sendeAnfrage(['Referer' => 'https://google.com/'], '?gclid=ERSTER-POST');

        $this->assertHerkunftZeilen($mail, 'google_ads', '/anfrage', 'google.com', 'gclid');
        $this->assertContains('ERSTER-POST', $this->herkunftWerte());
        $this->assertContains('/anfrage', $this->herkunftWerte());
    }

    // Randfall 7
    public function test_request_ohne_session_wird_von_der_middleware_ignoriert(): void
    {
        // Gegenprobe: Web-Route hat eine Session, API-Route nicht
        $this->besuche('/startseite')->assertOk()->assertJson(['session' => true]);
        $this->neueSession();

        $this->besuche('/api-ohne-session?gclid=API-GCLID', ['Referer' => 'https://google.com/'])
            ->assertOk()
            ->assertJson(['session' => false]);

        // Nichts davon ist in einer späteren Session gelandet
        $mail = $this->sendeAnfrage();
        $this->assertHerkunftZeilen($mail, 'direkt', '/anfrage', '-', '-');
        $this->assertNotContains('API-GCLID', $this->herkunftWerte());
    }

    // Randfall 8
    public function test_bestehende_datensaetze_bleiben_null_ohne_nachbefuellen(): void
    {
        $this->herkunftSpalteVorhanden();

        // Stand vor der Herkunft-Migration herstellen (letzte Migration zurückrollen)
        Artisan::call('migrate:rollback', ['--step' => 1, '--force' => true]);
        $this->assertFalse(Schema::hasColumn('kontaktformular', 'herkunft'), 'falsche Migration zurückgerollt');

        DB::table('kontaktformular')->insert([
            'jahr' => 2025, 'nummer' => 1, 'url' => 'http://localhost', 'name' => 'Alt',
            'email' => 'alt@example.com', 'nachricht' => 'Alte Anfrage mit Text.',
            'datenverarbeitung' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        // Besucher mit Herkunft in der Session, während migriert wird
        $this->besuche('/startseite?gclid=NICHT-NACHBEFUELLEN')->assertOk();
        Artisan::call('migrate', ['--force' => true]);

        $this->herkunftSpalteVorhanden();
        $this->assertDatabaseCount('kontaktformular', 1);
        $this->assertNull(DB::table('kontaktformular')->value('herkunft'));
    }

    // Randfall 9
    public function test_session_treiber_cookie_speichert_nichts_und_zeigt_direkt(): void
    {
        // Gegenprobe: gleiche Folge mit Treiber array ergibt google_ads
        $this->besuche('/excel-programmierer?gclid=TESTGCLID123', ['Referer' => 'https://google.com/'])->assertOk();
        $this->assertHerkunftZeilen($this->sendeAnfrage(), 'google_ads', '/excel-programmierer', 'google.com', 'gclid');
        $this->assertNotNull($this->herkunftRoh());

        $this->sessionTreiber('cookie');
        $this->besuche('/excel-programmierer?gclid=TESTGCLID123', ['Referer' => 'https://google.com/'])->assertOk();

        $mail = $this->sendeAnfrage();

        $this->assertNull($this->herkunftRoh());
        $this->assertDatabaseCount('kontaktformular', 2);
        $this->assertHerkunftZeilen($mail, 'direkt', '-', '-', '-');
    }
}
