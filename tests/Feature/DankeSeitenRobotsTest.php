<?php

declare(strict_types=1);

namespace ITHilbert\Kontaktformular\Tests\Feature;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use ITHilbert\Kontaktformular\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Bugfix danke-seiten-robots: Die Danke-Seiten dürfen keine Host-View
 * `header.robots_disallow` voraussetzen.
 */
final class DankeSeitenRobotsTest extends TestCase
{
    private const META = '<meta name="robots" content="noindex, nofollow">';

    private const MARKER = 'HOST-ROBOTS-DISALLOW-MARKER';

    private string $viewPfad;

    protected function setUp(): void
    {
        parent::setUp();

        // Host-Layouts als Stub bereitstellen (Vertrag: layouts.master / layouts.masterBot).
        $this->viewPfad = sys_get_temp_dir().'/kf-robots-test-'.bin2hex(random_bytes(6));
        File::ensureDirectoryExists($this->viewPfad.'/layouts');
        File::put($this->viewPfad.'/layouts/master.blade.php', "<html><head>@yield('meta')</head><body>@yield('content')</body></html>");
        File::put($this->viewPfad.'/layouts/masterBot.blade.php', "<html><head>@yield('meta')</head><body>@yield('content')</body></html>");
        View::addLocation($this->viewPfad);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->viewPfad);

        parent::tearDown();
    }

    private function hostRobotsViewBereitstellen(): void
    {
        File::ensureDirectoryExists($this->viewPfad.'/header');
        File::put($this->viewPfad.'/header/robots_disallow.blade.php', '<meta name="host-marker" content="'.self::MARKER.'">');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function dankeRouten(): array
    {
        return [
            'danke-formular' => ['danke_formular'],
            'danke-bot-formular' => ['danke_bot_formular'],
        ];
    }

    // AK1
    #[DataProvider('dankeRouten')]
    public function test_danke_seite_antwortet_200_ohne_host_view(string $route): void
    {
        $this->assertFalse(View::exists('header.robots_disallow'), 'Aufbau: Host-View darf nicht existieren.');

        $this->get(route($route))->assertOk();
    }

    // AK1
    #[DataProvider('dankeRouten')]
    public function test_danke_seite_enthaelt_genau_ein_robots_meta_ohne_host_view(string $route): void
    {
        $this->assertFalse(View::exists('header.robots_disallow'), 'Aufbau: Host-View darf nicht existieren.');

        $antwort = $this->get(route($route));

        $antwort->assertOk();
        $this->assertSame(1, substr_count($antwort->getContent(), self::META));
    }

    // Randfall 1
    #[DataProvider('dankeRouten')]
    public function test_danke_seite_bindet_host_view_nicht_ein_und_ak1_gilt(string $route): void
    {
        $this->hostRobotsViewBereitstellen();

        // Gegenprobe: Der Aufbau greift, die Host-View ist auffindbar und rendert ihren Marker.
        $this->assertTrue(View::exists('header.robots_disallow'));
        $this->assertStringContainsString(self::MARKER, view('header.robots_disallow')->render());

        $antwort = $this->get(route($route));

        $antwort->assertOk();
        $this->assertStringNotContainsString(self::MARKER, $antwort->getContent());
        $this->assertSame(1, substr_count($antwort->getContent(), self::META));
    }
}
