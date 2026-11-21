<?php

declare(strict_types=1);

namespace ITHilbert\Kontaktformular\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use ITHilbert\Kontaktformular\Mail\Anfrage;
use ITHilbert\Kontaktformular\Models\Kontaktformular;
use ITHilbert\Kontaktformular\Tests\TestCase;

final class PlainMailEntitiesTest extends TestCase
{
    private const URL = 'http://x.test/kontakt?utm_source=a&utm_medium=b';

    private const NAME = 'Müller & Söhne';

    private const NACHRICHT = 'a < b & "c"';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('kontaktformular.mailFrom', 'from@example.com');
        config()->set('kontaktformular.mailTo', 'to@example.com');
        config()->set('kontaktformular.subject', 'Test-Anfrage');
        config()->set('kontaktformular.mailBcc', null);
        config()->set('kontaktformular.fileDownloadUrl', 'http://localhost/kontaktformular/file/');

        Artisan::call('migrate', ['--force' => true]);
    }

    private function mail(): Anfrage
    {
        $kontakt = new Kontaktformular([
            'url' => self::URL,
            'name' => self::NAME,
            'email' => 'max@example.com',
            'nachricht' => self::NACHRICHT,
            'datenverarbeitung' => true,
            'herkunft' => ['einstieg' => '/start', 'referrer' => 'a&b.example.org'],
        ]);
        $kontakt->save();

        return new Anfrage($kontakt->fresh());
    }

    private function text(Anfrage $mail): string
    {
        return view('kontaktformular::mail.anfrage_plain', $mail->buildViewData())->render();
    }

    // AK1
    public function test_text_teil_nennt_site_mit_und_zeichen_ohne_entity(): void
    {
        $text = $this->text($this->mail());

        $this->assertStringContainsString('Site: '.self::URL, $text);
        $this->assertStringNotContainsString('&amp;', $text);
    }

    // AK2
    public function test_text_teil_gibt_name_und_mitteilung_unveraendert_aus(): void
    {
        $text = $this->text($this->mail());

        $this->assertStringContainsString('Name: '.self::NAME, $text);
        $this->assertStringContainsString('Mitteilung: '.self::NACHRICHT, $text);
        foreach (['&lt;', '&quot;', '&#039;', '&gt;'] as $entity) {
            $this->assertStringNotContainsString($entity, $text);
        }
    }

    // AK3 (Gegenprobe: der HTML-Teil rendert dieselbe Anfrage und bleibt escaped)
    public function test_html_teil_bleibt_escaped(): void
    {
        $mail = $this->mail();
        $html = $mail->render();

        // Gegenprobe: die Anfrage wird gerendert
        $this->assertStringContainsString('max@example.com', $html);
        $this->assertStringContainsString('Müller &amp; Söhne', $html);
        $this->assertStringContainsString('a &lt; b', $html);
        $this->assertStringNotContainsString('a < b', $html);
    }

    // Randfall Referrer
    public function test_text_teil_gibt_referrer_mit_und_zeichen_unescaped_aus(): void
    {
        $text = $this->text($this->mail());

        $this->assertStringContainsString('Referrer: a&b.example.org', $text);
        $this->assertStringNotContainsString('a&amp;b', $text);
    }
}
