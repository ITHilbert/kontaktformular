<?php

declare(strict_types=1);

namespace ITHilbert\Kontaktformular\Tests\Feature;

use ITHilbert\Kontaktformular\Tests\TestCase;

/**
 * WebMCP-Anmerkungen (Task #1425): Config, Durchreichen als Props, README.
 * Die Attribute der Vue-Komponente prüft tests/js/webmcpFormular.test.mjs; AK5 (Lighthouse) prüft der Moderator.
 */
final class WebmcpFormularTest extends TestCase
{
    private const ENV_NAME = 'KONTAKTFORMULAR_WEBMCP_NAME';

    private const ENV_BESCHREIBUNG = 'KONTAKTFORMULAR_WEBMCP_BESCHREIBUNG';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('kontaktformular.webmcp_name', null);
        config()->set('kontaktformular.webmcp_beschreibung', null);
    }

    protected function tearDown(): void
    {
        $this->umgebung(self::ENV_NAME, null);
        $this->umgebung(self::ENV_BESCHREIBUNG, null);
        parent::tearDown();
    }

    private function umgebung(string $name, ?string $wert): void
    {
        if ($wert === null) {
            unset($_ENV[$name], $_SERVER[$name]);
            putenv($name);

            return;
        }

        $_ENV[$name] = $wert;
        $_SERVER[$name] = $wert;
        putenv($name.'='.$wert);
    }

    /**
     * @return array<string, mixed>
     */
    private function paketConfig(): array
    {
        /** @var array<string, mixed> $config */
        $config = require dirname(__DIR__, 2).'/src/Config/config.php';

        return $config;
    }

    // AK2
    public function test_config_schluessel_haben_standard_null_und_folgen_den_env_namen(): void
    {
        $standard = $this->paketConfig();
        $this->assertArrayHasKey('webmcp_name', $standard);
        $this->assertArrayHasKey('webmcp_beschreibung', $standard);
        $this->assertNull($standard['webmcp_name']);
        $this->assertNull($standard['webmcp_beschreibung']);

        $this->umgebung(self::ENV_NAME, 'mein_tool');
        $this->umgebung(self::ENV_BESCHREIBUNG, 'Meine Beschreibung');
        $gesetzt = $this->paketConfig();
        $this->assertSame('mein_tool', $gesetzt['webmcp_name']);
        $this->assertSame('Meine Beschreibung', $gesetzt['webmcp_beschreibung']);
    }

    // AK2
    public function test_view_reicht_gesetzte_config_als_props_durch(): void
    {
        $beschreibung = 'Beschreibung "mit" Anführungszeichen & Und';
        config()->set('kontaktformular.webmcp_name', 'mein_tool');
        config()->set('kontaktformular.webmcp_beschreibung', $beschreibung);

        $html = view('kontaktformular::show')->render();

        $this->assertMatchesRegularExpression('/\btool-name="mein_tool"/', $html);
        $this->assertStringContainsString('tool-description="'.e($beschreibung).'"', $html);
    }

    // AK2
    public function test_view_setzt_ohne_config_keine_werte_und_die_komponente_nimmt_ihren_standard(): void
    {
        // Gegenprobe: mit Config erscheinen die Props
        config()->set('kontaktformular.webmcp_name', 'mein_tool');
        $this->assertMatchesRegularExpression('/\btool-name="mein_tool"/', view('kontaktformular::show')->render());

        config()->set('kontaktformular.webmcp_name', null);
        $html = view('kontaktformular::show')->render();

        $this->assertStringContainsString('<kontaktformular', $html);
        $this->assertDoesNotMatchRegularExpression('/\btool-name="[^"]+"/', $html);
        $this->assertDoesNotMatchRegularExpression('/\btool-description="[^"]+"/', $html);
    }

    // AK6
    public function test_readme_dokumentiert_config_props_wahl_und_eigene_kopie(): void
    {
        $readme = (string) file_get_contents(dirname(__DIR__, 2).'/README.md');
        // Gegenprobe: die gleiche Prüfung findet eine bestehende Einstellung
        $this->assertStringContainsString('KONTAKTFORMULAR_FELD_WEBSITE', $readme);

        foreach ([
            'webmcp_name', 'KONTAKTFORMULAR_WEBMCP_NAME',
            'webmcp_beschreibung', 'KONTAKTFORMULAR_WEBMCP_BESCHREIBUNG',
            'tool-name', 'tool-description',
            'kontaktformular_senden', 'toolautosubmit',
            'WebMCP', 'fax', 'Honeypot', 'Einwilligung',
        ] as $begriff) {
            $this->assertStringContainsString($begriff, $readme, "README nennt '{$begriff}' nicht");
        }

        // Restrisiko: Agent füllt den Honeypot trotzdem
        $this->assertMatchesRegularExpression('/Restrisiko/u', $readme);

        // Hosts mit eigener Kopie der Komponente bauen die Attribute selbst ein und bauen neu
        $this->assertMatchesRegularExpression(
            '/eigene[rn]? Kopie der (?:Vue-)?Komponente.{0,300}(?:toolname|Attribute).{0,300}(?:selbst|neu bauen)|(?:toolname|Attribute).{0,300}eigene[rn]? Kopie der (?:Vue-)?Komponente.{0,300}(?:selbst|neu bauen)/su',
            $readme,
            'README sagt nicht, dass Hosts mit eigener Kopie der Komponente die WebMCP-Attribute selbst einbauen'
        );
    }
}
