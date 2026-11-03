<?php

declare(strict_types=1);

namespace ITHilbert\Kontaktformular\Tests\Feature;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use ITHilbert\Kontaktformular\Mail\Anfrage;
use ITHilbert\Kontaktformular\Middleware\HerkunftErfassen;
use ITHilbert\Kontaktformular\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * AK10 (#366): Nur Seitenaufrufe zählen als Einstieg. Gelesen wird die Session direkt,
 * damit der Lesezugriff selbst keinen Seitenaufruf auslöst.
 */
final class HerkunftTechnischeRequestsTest extends TestCase
{
    private const string TOKEN_PFAD = '/kontaktformular/csrf-token';

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

    /**
     * @param  Router  $router
     */
    protected function defineRoutes($router): void
    {
        $router->middleware('web')->group(function () use ($router): void {
            $router->get('/startseite', fn () => response()->json(['ok' => true]));
            $router->get('/kontakt', fn () => response()->json(['ok' => true]));
            $router->match(['POST', 'PUT', 'PATCH', 'DELETE'], '/aktion', fn () => response()->json(['ok' => true]));
        });
    }

    // ------------------------------------------------------------------
    // Hilfen
    // ------------------------------------------------------------------

    /**
     * @return array<string, mixed>|null
     */
    private function herkunft(): ?array
    {
        $wert = $this->app->make('session')->driver()->get(HerkunftErfassen::SESSION_KEY);

        return is_array($wert) ? $wert : null;
    }

    private function neueSession(): void
    {
        $this->app->make('session')->forgetDrivers();
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

    /**
     * Gegenprobe: der nächste Seitenaufruf derselben Session wird nach AK1 erfasst.
     */
    private function assertNaechsterSeitenaufrufWirdErfasst(): void
    {
        $this->get('/kontakt?utm_source=x')->assertOk();

        $herkunft = $this->herkunft();
        $this->assertNotNull($herkunft, 'Der folgende Seitenaufruf wurde nicht erfasst');
        $this->assertSame('x', $herkunft['utm_source'] ?? null);
        $this->assertSame('/kontakt', $herkunft['einstieg'] ?? null);
    }

    // ------------------------------------------------------------------
    // Methode weder GET noch HEAD
    // ------------------------------------------------------------------

    // AK10
    public function test_post_anfrage_als_erster_request_speichert_keine_herkunft_in_der_session(): void
    {
        $this->post(route('anfrage').'?gclid=ERSTER-POST', $this->gueltigeAnfrage(), ['Referer' => 'https://google.com/'])
            ->assertRedirect(route('danke_formular'));

        $this->assertNull($this->herkunft(), 'POST /anfrage hat eine Herkunft gespeichert');
        // Gegenprobe: Der Aufbau greift, die Anfrage lief durch
        Mail::assertSent(Anfrage::class, 1);
    }

    // AK10
    public function test_post_anfrage_als_erster_request_laesst_spalte_herkunft_null_und_verschickt_die_anfrage(): void
    {
        $this->post(route('anfrage').'?gclid=ERSTER-POST', $this->gueltigeAnfrage(), ['Referer' => 'https://google.com/'])
            ->assertRedirect(route('danke_formular'));

        $this->assertSame(1, DB::table('kontaktformular')->count(), 'Anfrage nicht gespeichert');
        $this->assertNull(DB::table('kontaktformular')->value('herkunft'));
        Mail::assertSent(Anfrage::class, 1);
    }

    // AK10
    public function test_post_anfrage_als_erster_request_zeigt_in_der_mail_direkt_und_striche(): void
    {
        $this->post(route('anfrage'), $this->gueltigeAnfrage(), ['Referer' => 'https://google.com/'])
            ->assertRedirect(route('danke_formular'));

        $mail = Mail::sent(Anfrage::class)->last();
        $this->assertNotNull($mail, 'Keine Mail verschickt');
        $text = view('kontaktformular::mail.anfrage_plain', $mail->buildViewData())->render();
        $this->assertStringContainsString('Herkunft: direkt', $text);
        $this->assertMatchesRegularExpression('/^Einstieg:\s*-\s*$/m', $text);
        $this->assertMatchesRegularExpression('/^Referrer:\s*-\s*$/m', $text);
    }

    // AK10
    public function test_nach_post_anfrage_wird_der_naechste_seitenaufruf_erfasst(): void
    {
        $this->post(route('anfrage'), $this->gueltigeAnfrage())->assertRedirect(route('danke_formular'));
        $this->assertNull($this->herkunft());

        $this->assertNaechsterSeitenaufrufWirdErfasst();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function schreibendeMethoden(): array
    {
        return ['POST' => ['POST'], 'PUT' => ['PUT'], 'PATCH' => ['PATCH'], 'DELETE' => ['DELETE']];
    }

    // AK10
    #[DataProvider('schreibendeMethoden')]
    public function test_methode_ausser_get_und_head_speichert_keine_herkunft(string $methode): void
    {
        $this->call($methode, '/aktion?gclid=ABC&utm_source=google')->assertOk();

        $this->assertNull($this->herkunft(), "{$methode} hat eine Herkunft gespeichert");
        // Gegenprobe: derselbe Aufbau mit GET speichert (andere Session)
        $this->neueSession();
        $this->get('/startseite?utm_source=google')->assertOk();
        $this->assertNotNull($this->herkunft(), 'Aufbau greift nicht: GET speichert nicht');
    }

    // AK10
    #[DataProvider('schreibendeMethoden')]
    public function test_nach_methode_ausser_get_und_head_wird_der_naechste_seitenaufruf_erfasst(string $methode): void
    {
        $this->call($methode, '/aktion')->assertOk();
        $this->assertNull($this->herkunft());

        $this->assertNaechsterSeitenaufrufWirdErfasst();
    }

    // ------------------------------------------------------------------
    // expectsJson
    // ------------------------------------------------------------------

    // AK10
    public function test_request_mit_accept_json_speichert_keine_herkunft(): void
    {
        $this->getJson('/startseite?gclid=ABC')->assertOk();

        $this->assertNull($this->herkunft(), 'JSON-Request hat eine Herkunft gespeichert');
        // Gegenprobe: derselbe Pfad ohne JSON-Erwartung wird erfasst
        $this->get('/startseite?gclid=ABC')->assertOk();
        $this->assertSame('ABC', $this->herkunft()['gclid'] ?? null);
    }

    // AK10
    public function test_request_der_json_erwartet_per_xhr_header_speichert_keine_herkunft(): void
    {
        $this->get('/startseite?gclid=ABC', ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => '*/*'])->assertOk();

        $this->assertNull($this->herkunft(), 'XHR-Request hat eine Herkunft gespeichert');
        // Gegenprobe: ohne XHR-Header, mit Accept html, wird erfasst
        $this->get('/startseite?gclid=ABC', ['Accept' => 'text/html'])->assertOk();
        $this->assertSame('ABC', $this->herkunft()['gclid'] ?? null);
    }

    // AK10
    public function test_nach_json_request_wird_der_naechste_seitenaufruf_erfasst(): void
    {
        $this->getJson('/startseite')->assertOk();
        $this->assertNull($this->herkunft());

        $this->assertNaechsterSeitenaufrufWirdErfasst();
    }

    // ------------------------------------------------------------------
    // Route kontaktformular.csrf_token
    // ------------------------------------------------------------------

    // AK10
    public function test_csrf_token_route_als_erster_request_speichert_keine_herkunft(): void
    {
        $this->get(self::TOKEN_PFAD)->assertOk()->assertJsonStructure(['token']);

        $this->assertNull($this->herkunft(), 'Die Token-Route hat eine Herkunft gespeichert');
        // Gegenprobe: eine normale Seite (GET) wird erfasst
        $this->neueSession();
        $this->get('/startseite')->assertOk();
        $this->assertNotNull($this->herkunft(), 'Aufbau greift nicht: GET speichert nicht');
    }

    // AK10
    public function test_csrf_token_route_mit_parametern_speichert_nie_den_pfad_als_einstieg(): void
    {
        $this->get(self::TOKEN_PFAD.'?gclid=ABC&utm_source=x')->assertOk();

        $herkunft = $this->herkunft();
        $this->assertNull($herkunft, 'Token-Route: Herkunft gespeichert');
        // Gegenprobe: dieselben Parameter auf einer Seite werden erfasst
        $this->get('/kontakt?gclid=ABC&utm_source=x')->assertOk();
        $this->assertSame('/kontakt', $this->herkunft()['einstieg'] ?? null);
    }

    // AK10
    public function test_nach_csrf_token_route_wird_der_naechste_seitenaufruf_erfasst(): void
    {
        $this->get(self::TOKEN_PFAD)->assertOk();
        $this->assertNull($this->herkunft());

        $this->assertNaechsterSeitenaufrufWirdErfasst();
    }

    // AK10
    public function test_token_abruf_dann_seitenaufruf_dann_anfrage_speichert_herkunft_des_seitenaufrufs(): void
    {
        $this->get(self::TOKEN_PFAD)->assertOk();
        $this->get('/kontakt?utm_source=x')->assertOk();
        $this->post(route('anfrage'), $this->gueltigeAnfrage())->assertRedirect(route('danke_formular'));

        $roh = DB::table('kontaktformular')->value('herkunft');
        $this->assertNotNull($roh);
        $daten = json_decode((string) $roh, true);
        $this->assertSame('/kontakt', $daten['einstieg'] ?? null);
        $this->assertSame('x', $daten['utm_source'] ?? null);
    }

    // ------------------------------------------------------------------
    // GET und HEAD zählen weiter
    // ------------------------------------------------------------------

    // AK10
    public function test_head_request_erfasst_weiterhin_die_herkunft(): void
    {
        $this->call('HEAD', '/startseite?utm_source=x');

        $herkunft = $this->herkunft();
        $this->assertNotNull($herkunft, 'HEAD hat keine Herkunft erfasst');
        $this->assertSame('x', $herkunft['utm_source'] ?? null);
        $this->assertSame('/startseite', $herkunft['einstieg'] ?? null);
    }

    // AK10
    public function test_get_request_ohne_json_erwartung_erfasst_weiterhin_die_herkunft(): void
    {
        $this->get('/startseite?utm_source=x', ['Accept' => 'text/html'])->assertOk();

        $herkunft = $this->herkunft();
        $this->assertNotNull($herkunft);
        $this->assertSame('/startseite', $herkunft['einstieg'] ?? null);
    }

    // AK10 (technische Requests nach erfasstem Seitenaufruf ändern nichts, AK2)
    public function test_technische_requests_nach_erfasstem_seitenaufruf_aendern_die_herkunft_nicht(): void
    {
        $this->get('/startseite?utm_source=erste')->assertOk();
        $vorher = $this->herkunft();
        $this->assertSame('erste', $vorher['utm_source'] ?? null);

        $this->get(self::TOKEN_PFAD.'?utm_source=zweite')->assertOk();
        $this->postJson('/aktion?utm_source=dritte')->assertOk();

        $this->assertSame($vorher, $this->herkunft());
    }
}
