<?php

declare(strict_types=1);

namespace ITHilbert\Kontaktformular\Tests\Feature;

use PHPUnit\Framework\TestCase;

/**
 * Die README des Pakets beschreibt die Herkunftserfassung (AK9).
 */
final class HerkunftReadmeTest extends TestCase
{
    private function abschnitt(): string
    {
        $readme = (string) file_get_contents(dirname(__DIR__, 2).'/README.md');
        $start = strpos($readme, '## Herkunft der Anfrage');
        $this->assertNotFalse($start, 'Abschnitt "Herkunft der Anfrage" fehlt in der README');
        $rest = substr($readme, $start + 3);
        $ende = strpos($rest, "\n## ");

        return $ende === false ? $rest : substr($rest, 0, $ende);
    }

    // AK9
    public function test_readme_beschreibt_die_automatisch_in_web_eingehaengte_middleware(): void
    {
        $text = $this->abschnitt();

        $this->assertStringContainsString('HerkunftErfassen', $text);
        $this->assertMatchesRegularExpression('/automatisch in die Middleware-Gruppe `web` eingehängt/u', $text);
        $this->assertStringContainsString('Im Projekt ist nichts einzutragen', $text);
    }

    // AK9
    public function test_readme_nennt_die_erfassten_werte(): void
    {
        $text = $this->abschnitt();

        foreach (['gclid', 'gbraid', 'wbraid', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'einstieg', 'referrer', '`Referer`-Header'] as $wert) {
            $this->assertStringContainsString($wert, $text, "Erfasster Wert $wert fehlt in der README");
        }
    }

    // AK9
    public function test_readme_zeigt_die_neuen_mail_zeilen(): void
    {
        $text = $this->abschnitt();

        $this->assertMatchesRegularExpression(
            '/^Herkunft: google_ads\nEinstieg: \/excel-programmierer\nReferrer: google\.com\nKlick-ID: gclid$/m',
            $text,
        );
        $this->assertStringContainsString('direkt nach `Site:`', $text);
    }

    // AK9
    public function test_readme_beschreibt_die_spalte_herkunft(): void
    {
        $text = $this->abschnitt();

        $this->assertMatchesRegularExpression('/nullable JSON-Spalte `herkunft` in `kontaktformular`/u', $text);
        $this->assertStringContainsString('php artisan migrate', $text);
    }

    // AK9
    public function test_readme_hat_den_hinweis_fuer_die_datenschutzerklaerung(): void
    {
        $text = $this->abschnitt();

        $this->assertStringContainsString('Datenschutzerklärung', $text);
        $this->assertStringContainsString('Session', $text);
        $this->assertStringContainsString('keine neuen Cookies', $text);
    }
}
