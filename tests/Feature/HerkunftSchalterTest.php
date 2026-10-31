<?php

declare(strict_types=1);

namespace ITHilbert\Kontaktformular\Tests\Feature;

use Illuminate\Contracts\Http\Kernel as HttpKernelContract;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Routing\Router;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use ITHilbert\Kontaktformular\Mail\Anfrage;
use ITHilbert\Kontaktformular\Middleware\HerkunftErfassen;
use ITHilbert\Kontaktformular\Tests\TestCase;
use Orchestra\Testbench\Attributes\WithEnv;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Schalter `kontaktformular.herkunft_erfassen` (env KONTAKTFORMULAR_HERKUNFT_ERFASSEN).
 * Der Schalter wird über die Umgebung gesetzt (#[WithEnv]), bevor die App bootet.
 */
final class HerkunftSchalterTest extends TestCase
{
    private const string ENV = 'KONTAKTFORMULAR_HERKUNFT_ERFASSEN';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('kontaktformular.mailFrom', 'from@example.com');
        config()->set('kontaktformular.mailTo', 'to@example.com');
        config()->set('kontaktformular.subject', 'Test-Anfrage');
        config()->set('kontaktformular.mailBcc', null);
        config()->set('kontaktformular.fileDownloadUrl', 'http://localhost/kontaktformular/file/');

        Artisan::call('migrate', ['--force' => true]);

        Mail::fake();
    }

    protected function tearDown(): void
    {
        $this->umgebungLoeschen();
        parent::tearDown();
    }

    /**
     * @param  Router  $router
     */
    protected function defineRoutes($router): void
    {
        $router->middleware('web')->group(function () use ($router): void {
            $router->get('/excel-programmierer', fn () => response()->json(['session' => request()->hasSession()]));
            $router->get('/startseite', fn () => response()->json(['session' => request()->hasSession()]));

            $router->get('/merke', function () {
                session()->put('probe', 'vorhanden');

                return response()->json(['ok' => true]);
            });

            $router->get('/pruefe', fn () => response()->json([
                'probe' => session()->get('probe'),
                'herkunft' => session()->has(HerkunftErfassen::SESSION_KEY),
            ]));
        });
    }

    // ------------------------------------------------------------------
    // Hilfen
    // ------------------------------------------------------------------

    private function umgebungSetzen(?string $wert): void
    {
        if ($wert === null) {
            $this->umgebungLoeschen();

            return;
        }

        $_ENV[self::ENV] = $wert;
        $_SERVER[self::ENV] = $wert;
        putenv(self::ENV.'='.$wert);
    }

    private function umgebungLoeschen(): void
    {
        unset($_ENV[self::ENV], $_SERVER[self::ENV]);
        putenv(self::ENV);
    }

    /**
     * Liest die Config des Pakets neu ein, so wie eine App sie beim Boot liest.
     *
     * @return array<string, mixed>
     */
    private function paketConfig(): array
    {
        /** @var array<string, mixed> $config */
        $config = require dirname(__DIR__, 2).'/src/Config/config.php';

        return $config;
    }

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

    private function sendeAnfrage(?string $site = null): Anfrage
    {
        $vorher = Mail::sent(Anfrage::class)->count();

        $daten = $this->gueltigeAnfrage();
        if ($site !== null) {
            $daten['site'] = $site;
        }

        $this->post(route('anfrage'), $daten)->assertRedirect(route('danke_formular'));

        $gesendet = Mail::sent(Anfrage::class);
        $this->assertCount($vorher + 1, $gesendet, 'Anfrage wurde nicht verschickt');

        return $gesendet->last();
    }

    private function textWert(Anfrage $mail, string $praefix): ?string
    {
        $text = view('kontaktformular::mail.anfrage_plain', $mail->buildViewData())->render();
        foreach (preg_split('/\R/', $text) as $zeile) {
            $zeile = rtrim($zeile);
            if (str_starts_with($zeile, $praefix)) {
                return trim(substr($zeile, strlen($praefix)));
            }
        }

        return null;
    }

    private function htmlWert(Anfrage $mail, string $beschriftung): ?string
    {
        preg_match_all('#<tr\b[^>]*>(.*?)</tr>#s', $mail->render(), $zeilen);
        foreach ($zeilen[1] as $zeile) {
            preg_match_all('#<td\b[^>]*>(.*?)</td>#s', $zeile, $zellen);
            if (count($zellen[1]) >= 2 && trim(html_entity_decode(strip_tags($zellen[1][0]))) === $beschriftung) {
                return trim(html_entity_decode(strip_tags($zellen[1][1])));
            }
        }

        return null;
    }

    private function assertHerkunftZeilen(Anfrage $mail, string $herkunft, string $einstieg, string $referrer, string $klickId): void
    {
        $erwartet = ['Herkunft:' => $herkunft, 'Einstieg:' => $einstieg, 'Referrer:' => $referrer, 'Klick-ID:' => $klickId];

        foreach ($erwartet as $label => $wert) {
            $this->assertSame($wert, $this->textWert($mail, $label), "Text-Teil: {$label}");
            $this->assertSame($wert, $this->htmlWert($mail, $label), "HTML-Teil: {$label}");
        }
    }

    private function herkunftRoh(): ?string
    {
        $this->assertTrue(Schema::hasColumn('kontaktformular', 'herkunft'), 'Spalte herkunft fehlt');
        $wert = DB::table('kontaktformular')->orderByDesc('id')->value('herkunft');

        return $wert === null ? null : (string) $wert;
    }

    /**
     * @return array{probe: ?string, herkunft: bool}
     */
    private function sessionZustand(): array
    {
        return $this->get('/pruefe')->assertOk()->json();
    }

    /**
     * @return array<string, string>
     */
    private function sessionHerkunft(): array
    {
        return [
            'gclid' => 'ALT-GCLID',
            'utm_source' => 'google',
            'einstieg' => '/altlast',
            'referrer' => 'google.com',
        ];
    }

    private function webGruppeRouter(): array
    {
        return $this->app->make(Router::class)->getMiddlewareGroups()['web'] ?? [];
    }

    /**
     * Löst den HTTP-Kernel neu auf (wie eine App, die ihn erst nach dem Boot erzeugt).
     */
    private function spaetAufgeloesterKernel(): HttpKernel
    {
        $this->app->forgetInstance(HttpKernelContract::class);
        $kernel = $this->app->make(HttpKernelContract::class);
        $this->assertInstanceOf(HttpKernel::class, $kernel);

        return $kernel;
    }

    // ------------------------------------------------------------------
    // AK1 — Schalter und Cast in der Config
    // ------------------------------------------------------------------

    /**
     * @return array<string, array{0: string, 1: bool}>
     */
    public static function werteMitWertung(): array
    {
        return [
            'false' => ['false', false],
            '0' => ['0', false],
            'off' => ['off', false],
            'no' => ['no', false],
            'OFF groß' => ['OFF', false],
            'leer' => ['', false],
            'true' => ['true', true],
            '1' => ['1', true],
            'on' => ['on', true],
            'yes' => ['yes', true],
            'On gemischt' => ['On', true],
        ];
    }

    // AK1, Randfall „leer“
    #[DataProvider('werteMitWertung')]
    public function test_config_wertet_den_env_wert_wie_filter_var_als_bool(string $wert, bool $erwartet): void
    {
        $this->umgebungSetzen($wert);

        // Gegenprobe: der Wert kommt bei env() an (gesetzt, auch leer)
        $this->assertNotNull(Env::get(self::ENV), 'Umgebungswert kommt nicht an');

        $config = $this->paketConfig();

        $this->assertArrayHasKey('herkunft_erfassen', $config);
        $this->assertSame($erwartet, $config['herkunft_erfassen']);
    }

    // AK1
    public function test_config_ist_ohne_env_wert_an(): void
    {
        $this->umgebungSetzen(null);

        // Gegenprobe: die Variable ist wirklich nicht gesetzt
        $this->assertNull(Env::get(self::ENV));

        $config = $this->paketConfig();

        $this->assertArrayHasKey('herkunft_erfassen', $config);
        $this->assertSame(true, $config['herkunft_erfassen']);
    }

    // AK1
    #[WithEnv(self::ENV, 'off')]
    public function test_app_config_ist_bei_off_ein_bool_false(): void
    {
        $this->assertSame(false, config('kontaktformular.herkunft_erfassen'));
    }

    // AK1, AK6
    public function test_app_config_ist_ohne_env_ein_bool_true(): void
    {
        $this->assertSame(true, config('kontaktformular.herkunft_erfassen'));
    }

    // AK1, Randfall „leer“
    #[WithEnv(self::ENV, '')]
    public function test_app_config_ist_bei_leerem_env_ein_bool_false(): void
    {
        $this->assertSame(false, config('kontaktformular.herkunft_erfassen'));
    }

    // ------------------------------------------------------------------
    // AK2 — Aus: keine Middleware
    // ------------------------------------------------------------------

    // AK2
    #[WithEnv(self::ENV, 'off')]
    public function test_aus_router_gruppe_web_enthaelt_die_middleware_nicht(): void
    {
        // Gegenprobe: Schalter ist aus; der Kernel ist aufgelöst und hat die Gruppe web in den Router übernommen
        $this->assertSame(false, config('kontaktformular.herkunft_erfassen'));
        $this->spaetAufgeloesterKernel();
        $gruppe = $this->webGruppeRouter();
        $this->assertContains(StartSession::class, $gruppe);

        $this->assertNotContains(HerkunftErfassen::class, $gruppe);
    }

    // AK2
    #[WithEnv(self::ENV, 'off')]
    public function test_aus_router_prioritaet_enthaelt_die_middleware_nicht(): void
    {
        // Gegenprobe: Schalter ist aus; der Kernel ist aufgelöst und hat die Priorität in den Router übernommen
        $this->assertSame(false, config('kontaktformular.herkunft_erfassen'));
        $this->spaetAufgeloesterKernel();
        $prioritaet = $this->app->make(Router::class)->middlewarePriority;
        $this->assertContains(StartSession::class, $prioritaet);

        $this->assertNotContains(HerkunftErfassen::class, $prioritaet);
    }

    // AK2
    #[WithEnv(self::ENV, 'off')]
    public function test_aus_kernel_gruppe_web_und_prioritaet_enthalten_die_middleware_nicht_auch_spaet_aufgeloest(): void
    {
        // Gegenprobe: Schalter ist aus, Kernel ist aufgebaut
        $this->assertSame(false, config('kontaktformular.herkunft_erfassen'));
        $kernel = $this->spaetAufgeloesterKernel();
        $this->assertContains(StartSession::class, $kernel->getMiddlewareGroups()['web'] ?? []);
        $this->assertContains(StartSession::class, $kernel->getMiddlewarePriority());

        $this->assertNotContains(HerkunftErfassen::class, $kernel->getMiddlewareGroups()['web']);
        $this->assertNotContains(HerkunftErfassen::class, $kernel->getMiddlewarePriority());
    }

    // AK2
    #[WithEnv(self::ENV, '')]
    public function test_leer_gesetzt_haengt_die_middleware_ebenfalls_nirgends_ein(): void
    {
        $this->assertSame(false, config('kontaktformular.herkunft_erfassen'));
        $kernel = $this->spaetAufgeloesterKernel();
        $this->assertContains(StartSession::class, $this->webGruppeRouter());

        $this->assertNotContains(HerkunftErfassen::class, $this->webGruppeRouter());
        $this->assertNotContains(HerkunftErfassen::class, $this->app->make(Router::class)->middlewarePriority);
        $this->assertNotContains(HerkunftErfassen::class, $kernel->getMiddlewareGroups()['web']);
        $this->assertNotContains(HerkunftErfassen::class, $kernel->getMiddlewarePriority());
    }

    // ------------------------------------------------------------------
    // AK6 — Standard unverändert (Gegenstück zu AK2)
    // ------------------------------------------------------------------

    // AK6
    public function test_standard_haengt_die_middleware_in_router_und_kernel_ein(): void
    {
        $router = $this->app->make(Router::class);
        $kernel = $this->spaetAufgeloesterKernel();

        foreach ([
            'Router-Gruppe' => $this->webGruppeRouter(),
            'Router-Priorität' => $router->middlewarePriority,
            'Kernel-Gruppe' => $kernel->getMiddlewareGroups()['web'],
            'Kernel-Priorität' => $kernel->getMiddlewarePriority(),
        ] as $name => $liste) {
            $liste = array_values($liste);
            $start = array_search(StartSession::class, $liste, true);
            $this->assertNotFalse($start, "{$name}: StartSession fehlt");
            $this->assertSame(HerkunftErfassen::class, $liste[$start + 1] ?? null, "{$name}: HerkunftErfassen nicht direkt nach StartSession");
        }
    }

    // AK6
    public function test_standard_erfasst_herkunft_und_zeigt_sie_in_der_mail(): void
    {
        $this->get('/excel-programmierer?utm_source=x&gclid=y', ['Referer' => 'https://google.com/'])
            ->assertOk()
            ->assertJson(['session' => true]);
        $this->assertTrue($this->sessionZustand()['herkunft']);

        $mail = $this->sendeAnfrage();

        $this->assertNotNull($this->herkunftRoh());
        $this->assertHerkunftZeilen($mail, 'google_ads', '/excel-programmierer', 'google.com', 'gclid');
    }

    // ------------------------------------------------------------------
    // AK3 — Aus: keine Herkunft gespeichert
    // ------------------------------------------------------------------

    // AK3
    #[WithEnv(self::ENV, 'off')]
    public function test_aus_legt_keinen_herkunfts_eintrag_in_der_session_an(): void
    {
        $this->assertSame(false, config('kontaktformular.herkunft_erfassen'));

        $this->get('/merke')->assertOk();
        $this->get('/excel-programmierer?utm_source=x&gclid=y', ['Referer' => 'https://google.com/'])
            ->assertOk()
            ->assertJson(['session' => true]);

        // Gegenprobe: die Session wird geladen (früherer Eintrag liegt vor), der Herkunfts-Eintrag fehlt
        $zustand = $this->sessionZustand();
        $this->assertSame('vorhanden', $zustand['probe']);
        $this->assertFalse($zustand['herkunft']);
    }

    // AK3
    #[WithEnv(self::ENV, 'off')]
    public function test_aus_speichert_keine_herkunft_in_der_spalte(): void
    {
        $this->assertSame(false, config('kontaktformular.herkunft_erfassen'));
        $this->get('/excel-programmierer?utm_source=x&gclid=y', ['Referer' => 'https://google.com/'])->assertOk();

        $this->sendeAnfrage();

        // Gegenprobe: die Anfrage wurde gespeichert
        $this->assertDatabaseCount('kontaktformular', 1);
        $this->assertDatabaseHas('kontaktformular', ['email' => 'max@example.com']);
        $this->assertNull($this->herkunftRoh());
    }

    // AK3
    #[WithEnv(self::ENV, 'off')]
    public function test_aus_mail_zeigt_direkt_und_striche_in_text_und_html(): void
    {
        $this->assertSame(false, config('kontaktformular.herkunft_erfassen'));
        $this->get('/excel-programmierer?utm_source=x&gclid=y', ['Referer' => 'https://google.com/'])->assertOk();

        $mail = $this->sendeAnfrage();

        // Gegenprobe: die vier Zeilen sind in der Mail vorhanden (nur mit anderen Werten)
        $this->assertNotNull($this->textWert($mail, 'Herkunft:'));
        $this->assertNotNull($this->htmlWert($mail, 'Klick-ID:'));
        $this->assertHerkunftZeilen($mail, 'direkt', '-', '-', '-');
    }

    // AK3, Randfall „leer“
    #[WithEnv(self::ENV, '')]
    public function test_leer_gesetzt_erfasst_ebenfalls_nichts(): void
    {
        $this->assertSame(false, config('kontaktformular.herkunft_erfassen'));
        $this->get('/merke')->assertOk();
        $this->get('/excel-programmierer?utm_source=x&gclid=y', ['Referer' => 'https://google.com/'])->assertOk();

        $zustand = $this->sessionZustand();
        $this->assertSame('vorhanden', $zustand['probe']);
        $this->assertFalse($zustand['herkunft']);

        $mail = $this->sendeAnfrage();

        $this->assertNull($this->herkunftRoh());
        $this->assertHerkunftZeilen($mail, 'direkt', '-', '-', '-');
    }

    // ------------------------------------------------------------------
    // AK4 — Aus: vorhandene Session-Herkunft wird nicht übernommen
    // ------------------------------------------------------------------

    // AK4
    #[WithEnv(self::ENV, 'off')]
    public function test_aus_uebernimmt_vorhandene_session_herkunft_nicht_in_die_spalte(): void
    {
        $this->assertSame(false, config('kontaktformular.herkunft_erfassen'));
        $this->withSession([HerkunftErfassen::SESSION_KEY => $this->sessionHerkunft()]);

        // Gegenprobe: die Herkunft liegt vor dem Absenden in der Session
        $this->assertTrue($this->sessionZustand()['herkunft']);

        $this->sendeAnfrage();

        $this->assertDatabaseCount('kontaktformular', 1);
        $this->assertNull($this->herkunftRoh());
    }

    // AK4
    #[WithEnv(self::ENV, 'off')]
    public function test_aus_zeigt_vorhandene_session_herkunft_nicht_in_der_mail(): void
    {
        $this->assertSame(false, config('kontaktformular.herkunft_erfassen'));
        $this->withSession([HerkunftErfassen::SESSION_KEY => $this->sessionHerkunft()]);
        $this->assertTrue($this->sessionZustand()['herkunft']);

        $mail = $this->sendeAnfrage();

        $this->assertHerkunftZeilen($mail, 'direkt', '-', '-', '-');
    }

    // AK4 (Gegenstück: bei an wird dieselbe Session-Herkunft übernommen — Aufbau greift)
    public function test_an_uebernimmt_dieselbe_session_herkunft(): void
    {
        $this->withSession([HerkunftErfassen::SESSION_KEY => $this->sessionHerkunft()]);
        $this->assertTrue($this->sessionZustand()['herkunft']);

        $mail = $this->sendeAnfrage();

        $this->assertNotNull($this->herkunftRoh());
        $this->assertHerkunftZeilen($mail, 'google_ads', '/altlast', 'google.com', 'gclid');
    }

    // Randfall „Controller liest den Wert pro Anfrage“
    public function test_controller_liest_den_schalter_pro_anfrage(): void
    {
        $this->withSession([HerkunftErfassen::SESSION_KEY => $this->sessionHerkunft()]);

        // Gegenprobe: bei an wird die Herkunft gespeichert
        $this->sendeAnfrage();
        $this->assertNotNull($this->herkunftRoh());

        config(['kontaktformular.herkunft_erfassen' => false]);
        $this->assertTrue($this->sessionZustand()['herkunft']);

        $mail = $this->sendeAnfrage();

        $this->assertDatabaseCount('kontaktformular', 2);
        $this->assertNull($this->herkunftRoh());
        $this->assertHerkunftZeilen($mail, 'direkt', '-', '-', '-');
    }

    // ------------------------------------------------------------------
    // AK5 — Klick-ID aus `site` bleibt entfernt
    // ------------------------------------------------------------------

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function siteMitKlickId(): array
    {
        $b = 'https://example.com/excel-programmierer';

        return [
            'gclid' => [$b.'?gclid=GEHEIM1&utm_source=google&x=1', $b.'?utm_source=google&x=1'],
            'gbraid' => [$b.'?gbraid=GEHEIM1&utm_source=google&x=1', $b.'?utm_source=google&x=1'],
            'wbraid' => [$b.'?wbraid=GEHEIM1&utm_source=google&x=1', $b.'?utm_source=google&x=1'],
            'alle drei' => [$b.'?gclid=GEHEIM1&gbraid=GEHEIM1&wbraid=GEHEIM1', $b],
        ];
    }

    // AK5
    #[DataProvider('siteMitKlickId')]
    #[WithEnv(self::ENV, 'off')]
    public function test_aus_entfernt_klick_ids_aus_site(string $eingabe, string $erwartet): void
    {
        // Gegenprobe: Schalter ist aus, die Eingabe enthält die Klick-ID wirklich
        $this->assertSame(false, config('kontaktformular.herkunft_erfassen'));
        $this->assertStringContainsString('GEHEIM1', $eingabe);

        $mail = $this->sendeAnfrage($eingabe);

        $this->assertSame($erwartet, DB::table('kontaktformular')->orderByDesc('id')->value('url'));
        $this->assertSame($erwartet, html_entity_decode((string) $this->textWert($mail, 'Site:')));
        $this->assertSame($erwartet, $this->htmlWert($mail, 'Site:'));
        $this->assertStringNotContainsString('GEHEIM1', $mail->render());
    }
}
