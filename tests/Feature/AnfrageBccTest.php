<?php

declare(strict_types=1);

namespace ITHilbert\Kontaktformular\Tests\Feature;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use ITHilbert\Kontaktformular\Mail\Anfrage;
use ITHilbert\Kontaktformular\Tests\TestCase;

final class AnfrageBccTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('kontaktformular.mailFrom', 'from@example.com');
        config()->set('kontaktformular.mailTo', 'to@example.com');
        config()->set('kontaktformular.subject', 'Test-Anfrage');
        config()->set('kontaktformular.fileDownloadUrl', 'http://localhost/kontaktformular/file/');

        Schema::dropIfExists('kontaktformular');
        Schema::create('kontaktformular', function ($table) {
            $table->id();
            $table->integer('jahr');
            $table->integer('nummer');
            $table->string('url');
            $table->string('name');
            $table->string('email');
            $table->string('telefon')->nullable();
            $table->longText('nachricht');
            $table->boolean('datenverarbeitung')->default(false);
            $table->string('file_name')->nullable();
            $table->string('file_type')->nullable();
            $table->string('file_hash')->nullable();
            $table->string('file_size')->nullable();
            $table->timestamp('file_downloaded_at')->nullable();
            $table->timestamp('file_deleted_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
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
     * Schickt eine gültige Anfrage und liefert die versendete Mail (gebaut).
     */
    private function sendeAnfrage(mixed $mailBcc): Anfrage
    {
        config()->set('kontaktformular.mailBcc', $mailBcc);
        Mail::fake();

        $this->post(route('anfrage'), $this->gueltigeAnfrage())
            ->assertRedirect(route('danke_formular'));

        $gesendet = Mail::sent(Anfrage::class);
        $this->assertCount(1, $gesendet);

        $mail = $gesendet->first();
        $mail->render();

        return $mail;
    }

    /**
     * @return list<string>
     */
    private function bccAdressen(Anfrage $mail): array
    {
        return array_values(array_unique(array_map(
            static fn (array $empfaenger): string => $empfaenger['address'],
            $mail->bcc
        )));
    }

    // AK1
    public function test_einzelne_adresse_wird_bcc_empfaenger(): void
    {
        $mail = $this->sendeAnfrage('lead@example.com');

        $this->assertSame(['lead@example.com'], $this->bccAdressen($mail));
    }

    // AK1
    public function test_mail_to_bleibt_bei_gesetztem_bcc_unveraendert(): void
    {
        $mail = $this->sendeAnfrage('lead@example.com');

        $this->assertTrue($mail->hasBcc('lead@example.com'));
        $this->assertTrue($mail->hasTo('to@example.com'));
        $this->assertFalse($mail->hasTo('lead@example.com'));
        $this->assertCount(1, $mail->to);
    }

    // AK1
    public function test_mehrere_adressen_im_array_sind_alle_bcc(): void
    {
        $mail = $this->sendeAnfrage(['a@x.de', 'b@y.de']);

        $this->assertSame(['a@x.de', 'b@y.de'], $this->bccAdressen($mail));
        $this->assertTrue($mail->hasTo('to@example.com'));
    }

    // Randfall 1
    public function test_array_aus_der_veroeffentlichten_config_ergibt_alle_adressen_als_bcc(): void
    {
        $mail = $this->sendeAnfrage(['a@x.de', 'b@y.de']);

        $this->assertTrue($mail->hasBcc('a@x.de'));
        $this->assertTrue($mail->hasBcc('b@y.de'));
        $this->assertCount(2, $this->bccAdressen($mail));
    }

    // Randfall 2
    public function test_komma_getrennter_string_ergibt_beide_adressen_als_bcc(): void
    {
        $mail = $this->sendeAnfrage('a@x.de, b@y.de');

        $this->assertTrue($mail->hasBcc('a@x.de'));
        $this->assertTrue($mail->hasBcc('b@y.de'));
        $this->assertCount(2, $this->bccAdressen($mail));
    }

    // Randfall 2
    public function test_leerzeichen_um_die_adressen_werden_ignoriert(): void
    {
        $mail = $this->sendeAnfrage('  a@x.de  ,   b@y.de ');

        $this->assertSame(['a@x.de', 'b@y.de'], $this->bccAdressen($mail));
    }

    // Randfall 2
    public function test_leerer_teil_nach_komma_ergibt_keinen_empfaenger(): void
    {
        $mail = $this->sendeAnfrage('a@x.de,');

        $this->assertTrue($mail->hasBcc('a@x.de'));
        $this->assertCount(1, $mail->bcc);
        $this->assertSame(['a@x.de'], $this->bccAdressen($mail));
    }

    // Randfall 2
    public function test_leere_teile_in_der_mitte_und_nur_leerzeichen_ergeben_keinen_empfaenger(): void
    {
        $mail = $this->sendeAnfrage('a@x.de, ,, b@y.de');

        $this->assertSame(['a@x.de', 'b@y.de'], $this->bccAdressen($mail));
        $this->assertCount(2, $mail->bcc);
    }

    // AK2
    public function test_nicht_gesetzter_wert_ergibt_kein_bcc(): void
    {
        // Gegenprobe: mit gesetztem Wert entsteht ein BCC-Empfänger
        $this->assertTrue($this->sendeAnfrage('lead@example.com')->hasBcc('lead@example.com'));

        config()->offsetUnset('kontaktformular.mailBcc');
        $this->assertNull(config('kontaktformular.mailBcc'));
        Mail::fake();
        $this->post(route('anfrage'), $this->gueltigeAnfrage())->assertRedirect(route('danke_formular'));

        $mail = Mail::sent(Anfrage::class)->first();
        $this->assertNotNull($mail);
        $mail->render();

        $this->assertSame([], $mail->bcc);
        $this->assertTrue($mail->hasTo('to@example.com'));
    }

    // AK2
    public function test_leerer_string_ergibt_kein_bcc(): void
    {
        $this->assertTrue($this->sendeAnfrage('lead@example.com')->hasBcc('lead@example.com'));

        $mail = $this->sendeAnfrage('');

        $this->assertSame([], $mail->bcc);
        $this->assertTrue($mail->hasTo('to@example.com'));
    }

    // AK2
    public function test_null_und_leeres_array_ergeben_kein_bcc(): void
    {
        $this->assertTrue($this->sendeAnfrage(['lead@example.com'])->hasBcc('lead@example.com'));

        $this->assertSame([], $this->sendeAnfrage(null)->bcc);
        $this->assertSame([], $this->sendeAnfrage([])->bcc);
    }

    // AK2, Randfall 2
    public function test_nur_leerzeichen_und_kommas_ergeben_kein_bcc(): void
    {
        $this->assertTrue($this->sendeAnfrage('lead@example.com')->hasBcc('lead@example.com'));

        $this->assertSame([], $this->sendeAnfrage('  ')->bcc);
        $this->assertSame([], $this->sendeAnfrage(' , ,')->bcc);
    }

    /**
     * Sendet dieselbe Anfrage (gleiche Zeit, gleiche Nummer) und liefert Metadaten und Inhalte.
     *
     * @return array<string, mixed>
     */
    private function formatDerAnfrage(mixed $mailBcc): array
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 26, 12, 0, 0));
        $this->app['db']->table('kontaktformular')->delete();
        $this->app['db']->table('sqlite_sequence')->where('name', 'kontaktformular')->delete();

        $mail = $this->sendeAnfrage($mailBcc);

        return [
            'from' => $mail->from,
            'subject' => $mail->subject,
            'replyTo' => $mail->replyTo,
            'html' => $mail->render(),
            'text' => view('kontaktformular::mail.anfrage_plain', $mail->buildViewData())->render(),
        ];
    }

    // AK3
    public function test_absender_betreff_und_reply_to_sind_mit_bcc_gleich(): void
    {
        $ohne = $this->formatDerAnfrage('');
        $mit = $this->formatDerAnfrage('lead@example.com');

        // Gegenprobe: Aufbau liefert echte Werte
        $this->assertSame('from@example.com', $ohne['from'][0]['address']);
        $this->assertSame('Test-Anfrage [1]', $ohne['subject']);
        $this->assertSame('max@example.com', $ohne['replyTo'][0]['address']);

        $this->assertEquals($ohne['from'], $mit['from']);
        $this->assertSame($ohne['subject'], $mit['subject']);
        $this->assertEquals($ohne['replyTo'], $mit['replyTo']);
    }

    // AK3
    public function test_html_und_text_inhalt_sind_mit_bcc_gleich(): void
    {
        $ohne = $this->formatDerAnfrage('');
        $mit = $this->formatDerAnfrage('a@x.de, b@y.de');

        $this->assertStringContainsString('Max Mustermann', $ohne['html']);
        $this->assertStringContainsString('Max Mustermann', $ohne['text']);

        $this->assertSame($ohne['html'], $mit['html']);
        $this->assertSame($ohne['text'], $mit['text']);
    }

    // AK3
    public function test_zeilen_nummer_datum_und_site_im_text_teil_bleiben_unveraendert(): void
    {
        $ohne = $this->formatDerAnfrage('');
        $mit = $this->formatDerAnfrage('lead@example.com');

        foreach (['Nummer:', 'Datum:', 'Site:'] as $praefix) {
            $zeileOhne = $this->zeile($ohne['text'], $praefix);
            $zeileMit = $this->zeile($mit['text'], $praefix);

            $this->assertNotNull($zeileOhne, "Zeile {$praefix} fehlt ohne BCC");
            $this->assertSame($zeileOhne, $zeileMit, "Zeile {$praefix} ändert sich mit BCC");
        }

        $this->assertSame('Nummer: 1', $this->zeile($mit['text'], 'Nummer:'));
        $this->assertSame('Site: http://localhost', $this->zeile($mit['text'], 'Site:'));
    }

    private function zeile(string $text, string $praefix): ?string
    {
        foreach (preg_split('/\R/', $text) as $zeile) {
            if (str_starts_with($zeile, $praefix)) {
                return rtrim($zeile);
            }
        }

        return null;
    }

    // Randfall 3
    public function test_honeypot_erzeugt_keine_mail_und_keine_kopie(): void
    {
        config()->set('kontaktformular.mailBcc', 'lead@example.com');
        Mail::fake();

        // Gegenprobe: gültige Anfrage erzeugt mit demselben Config eine Mail mit BCC
        $this->post(route('anfrage'), $this->gueltigeAnfrage())->assertRedirect(route('danke_formular'));
        $this->assertCount(1, Mail::sent(Anfrage::class));
        $this->assertTrue(Mail::sent(Anfrage::class)->first()->hasBcc('lead@example.com'));

        $this->post(route('anfrage'), $this->gueltigeAnfrage() + ['fax' => 'gefuellt'])
            ->assertRedirect(route('danke_bot_formular'));

        $this->assertCount(1, Mail::sent(Anfrage::class));
        $this->assertDatabaseCount('kontaktformular', 1);
    }

    // Randfall 3
    public function test_zu_kurze_nachricht_erzeugt_keine_mail_und_keine_kopie(): void
    {
        config()->set('kontaktformular.mailBcc', 'lead@example.com');
        Mail::fake();

        // Gegenprobe
        $this->post(route('anfrage'), $this->gueltigeAnfrage())->assertRedirect(route('danke_formular'));
        $this->assertCount(1, Mail::sent(Anfrage::class));
        $this->assertTrue(Mail::sent(Anfrage::class)->first()->hasBcc('lead@example.com'));

        $this->post(route('anfrage'), ['Nachricht' => 'kurz'] + $this->gueltigeAnfrage())
            ->assertRedirect(route('danke_bot_formular'));

        $this->assertCount(1, Mail::sent(Anfrage::class));
        $this->assertDatabaseCount('kontaktformular', 1);
    }
}
