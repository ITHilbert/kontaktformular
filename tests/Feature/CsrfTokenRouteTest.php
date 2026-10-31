<?php

declare(strict_types=1);

namespace ITHilbert\Kontaktformular\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use ITHilbert\Kontaktformular\Tests\TestCase;

/**
 * Token-Route GET /kontaktformular/csrf-token (Dashboard #120).
 *
 * Laravel überspringt die CSRF-Prüfung, wenn die App-Umgebung "testing" ist.
 * Darum schaltet der Test die Umgebung nach dem Booten auf "production";
 * jeder Test zeigt mit einer Gegenprobe, dass die Prüfung wirklich aktiv ist.
 */
final class CsrfTokenRouteTest extends TestCase
{
    private const TOKEN_PFAD = '/kontaktformular/csrf-token';

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

        // CSRF-Prüfung scharf schalten (runningUnitTests() liest die Umgebung)
        $this->app['env'] = 'production';
    }

    /**
     * @return array<string, string>
     */
    private function gueltigeAnfrage(?string $token = null): array
    {
        $daten = [
            'Name' => 'Max Mustermann',
            'Email' => 'max@example.com',
            'Telefon' => '+49 1234 567890',
            'Nachricht' => 'Das ist eine valide Testnachricht mit genug Inhalt.',
            'datenverarbeitung' => '1',
            'site' => 'http://localhost',
        ];

        if ($token !== null) {
            $daten['_token'] = $token;
        }

        return $daten;
    }

    private function neueSession(): void
    {
        $this->app->make('session')->forgetDrivers();
    }

    private function sitzungsCookie(TestResponse $response): string
    {
        $cookie = $response->getCookie(config('session.cookie'));
        $this->assertNotNull($cookie, 'Antwort setzt kein Sitzungs-Cookie');

        return $cookie->getValue();
    }

    private function holeToken(?string $sitzungsId = null): TestResponse
    {
        // Ohne Cookie beginnt eine neue Sitzung (der Array-Treiber würde sonst alte Werte weiterreichen)
        if ($sitzungsId === null) {
            $this->neueSession();
        }

        $test = $sitzungsId === null ? $this : $this->withCookie(config('session.cookie'), $sitzungsId);

        return $test->getJson(self::TOKEN_PFAD);
    }

    private function sendeMitSitzung(string $sitzungsId, array $daten): TestResponse
    {
        return $this->withCookie(config('session.cookie'), $sitzungsId)->post(route('anfrage'), $daten);
    }

    /**
     * Gegenprobe: ohne Token wird 419 geliefert, die CSRF-Prüfung greift also.
     */
    private function assertCsrfPruefungAktiv(): void
    {
        $this->post(route('anfrage'), $this->gueltigeAnfrage())->assertStatus(419);
    }

    // AK1
    public function test_route_hat_den_namen_und_liegt_in_der_web_gruppe(): void
    {
        $route = $this->app['router']->getRoutes()->getByName('kontaktformular.csrf_token');

        $this->assertNotNull($route, 'Route kontaktformular.csrf_token fehlt');
        $this->assertSame('kontaktformular/csrf-token', $route->uri());
        $this->assertContains('GET', $route->methods());
        $this->assertContains('web', $route->gatherMiddleware());
    }

    // AK1
    public function test_route_antwortet_200_mit_json_token(): void
    {
        $antwort = $this->holeToken();

        $antwort->assertOk();
        $token = $antwort->json('token');
        $this->assertIsString($token);
        $this->assertNotSame('', $token);
        $this->assertSame(['token'], array_keys($antwort->json()));
    }

    // AK1
    public function test_route_liefert_cache_control_no_store(): void
    {
        $antwort = $this->holeToken()->assertOk();

        $this->assertStringContainsString('no-store', (string) $antwort->headers->get('Cache-Control'));
    }

    // AK1
    public function test_token_ist_das_csrf_token_der_sitzung(): void
    {
        $erste = $this->holeToken()->assertOk();
        $sitzungsId = $this->sitzungsCookie($erste);

        // Gegenprobe: eine zweite Anfrage in derselben Sitzung liefert dasselbe Token
        $zweite = $this->holeToken($sitzungsId)->assertOk();
        $this->assertSame($erste->json('token'), $zweite->json('token'));

        $this->assertSame($erste->json('token'), $this->app['session.store']->token());
    }

    // AK1
    public function test_ohne_sitzungs_cookie_entsteht_eine_neue_sitzung_zu_der_das_token_gehoert(): void
    {
        $erste = $this->holeToken()->assertOk();
        $sitzungA = $this->sitzungsCookie($erste);

        // Token A gehört zu Sitzung A
        $this->sendeMitSitzung($sitzungA, $this->gueltigeAnfrage($erste->json('token')))
            ->assertRedirect(route('danke_formular'));

        // Ohne Cookie beginnt eine neue Sitzung B mit eigenem Token; Token A passt dort nicht
        $zweite = $this->holeToken()->assertOk();
        $sitzungB = $this->sitzungsCookie($zweite);
        $this->assertNotSame($sitzungA, $sitzungB);
        $this->assertNotSame($erste->json('token'), $zweite->json('token'));

        $this->sendeMitSitzung($sitzungB, $this->gueltigeAnfrage($erste->json('token')))->assertStatus(419);
        $this->sendeMitSitzung($sitzungB, $this->gueltigeAnfrage($zweite->json('token')))
            ->assertRedirect(route('danke_formular'));
    }

    // AK2
    public function test_abgelaufene_sitzung_altes_token_419_frisches_token_angenommen(): void
    {
        // Besucher lädt das Formular: Token der ersten Sitzung
        $alt = $this->holeToken()->assertOk();
        $altesToken = $alt->json('token');
        $alteSitzung = $this->sitzungsCookie($alt);

        // Gegenprobe: vor dem Ablauf ist das alte Token gültig
        $this->sendeMitSitzung($alteSitzung, $this->gueltigeAnfrage($altesToken))
            ->assertRedirect(route('danke_formular'));

        // Sitzung läuft ab
        $this->neueSession();

        // Mit dem alten Token und altem Cookie: 419
        $this->sendeMitSitzung($alteSitzung, $this->gueltigeAnfrage($altesToken))->assertStatus(419);

        // Frisches Token von der Route, Cookie dieser Antwort: angenommen
        $frisch = $this->holeToken($alteSitzung)->assertOk();
        $this->assertNotSame($altesToken, $frisch->json('token'));

        $this->sendeMitSitzung($this->sitzungsCookie($frisch), $this->gueltigeAnfrage($frisch->json('token')))
            ->assertRedirect(route('danke_formular'));
    }

    // AK2
    public function test_frisches_token_ohne_altes_sitzungs_cookie_wird_angenommen(): void
    {
        $this->assertCsrfPruefungAktiv();

        $frisch = $this->holeToken()->assertOk();

        $this->sendeMitSitzung($this->sitzungsCookie($frisch), $this->gueltigeAnfrage($frisch->json('token')))
            ->assertRedirect(route('danke_formular'));
        $this->assertDatabaseHas('kontaktformular', ['email' => 'max@example.com']);
    }

    // AK5
    public function test_post_ohne_sitzungs_cookie_und_ohne_token_ergibt_419(): void
    {
        // Gegenprobe: mit Cookie und Token dieselbe Anfrage angenommen
        $frisch = $this->holeToken()->assertOk();
        $this->sendeMitSitzung($this->sitzungsCookie($frisch), $this->gueltigeAnfrage($frisch->json('token')))
            ->assertRedirect(route('danke_formular'));

        $this->post(route('anfrage'), $this->gueltigeAnfrage())->assertStatus(419);
        $this->assertDatabaseCount('kontaktformular', 1);
    }

    // AK5
    public function test_post_mit_fremdem_token_ohne_sitzungs_cookie_ergibt_419(): void
    {
        $fremd = $this->holeToken()->assertOk();
        $eigene = $this->holeToken()->assertOk();

        // Gegenprobe: Token gehört zu seiner Sitzung
        $this->sendeMitSitzung($this->sitzungsCookie($eigene), $this->gueltigeAnfrage($eigene->json('token')))
            ->assertRedirect(route('danke_formular'));

        $this->neueSession();
        $this->post(route('anfrage'), $this->gueltigeAnfrage($fremd->json('token')))->assertStatus(419);
        $this->assertDatabaseCount('kontaktformular', 1);
    }

    // AK5
    public function test_post_mit_falschem_token_in_gueltiger_sitzung_ergibt_419(): void
    {
        $frisch = $this->holeToken()->assertOk();
        $sitzung = $this->sitzungsCookie($frisch);

        // Gegenprobe: richtiges Token in derselben Sitzung wird angenommen
        $this->sendeMitSitzung($sitzung, $this->gueltigeAnfrage($frisch->json('token')))
            ->assertRedirect(route('danke_formular'));

        $this->sendeMitSitzung($sitzung, $this->gueltigeAnfrage('falsches-token'))->assertStatus(419);
        $this->sendeMitSitzung($sitzung, $this->gueltigeAnfrage(''))->assertStatus(419);
        $this->assertDatabaseCount('kontaktformular', 1);
    }

    // AK5
    public function test_anfrage_route_steht_in_keiner_csrf_ausnahme(): void
    {
        $this->assertCsrfPruefungAktiv();

        $this->post('/anfrage', $this->gueltigeAnfrage(), ['Accept' => 'application/json'])->assertStatus(419);
        $this->post(self::TOKEN_PFAD, [])->assertStatus(405);
    }

    // Randfall 2
    public function test_route_setzt_keine_cors_header(): void
    {
        $antwort = $this->getJson(self::TOKEN_PFAD, ['Origin' => 'https://fremd.example.org'])->assertOk();

        $this->assertNull($antwort->headers->get('Access-Control-Allow-Origin'));
        $this->assertNull($antwort->headers->get('Access-Control-Allow-Credentials'));

        // Gegenprobe: Preflight wird nicht freigegeben
        $preflight = $this->call('OPTIONS', self::TOKEN_PFAD, [], [], [], [
            'HTTP_ORIGIN' => 'https://fremd.example.org',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
        ]);
        $this->assertNull($preflight->headers->get('Access-Control-Allow-Origin'));
    }
}
