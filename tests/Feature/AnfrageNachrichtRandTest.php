<?php

declare(strict_types=1);

namespace ITHilbert\Kontaktformular\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use ITHilbert\Kontaktformular\Mail\Anfrage;
use ITHilbert\Kontaktformular\Tests\TestCase;

/**
 * Nachricht: gespeichert getrimmt (AK13), Nicht-Zeichenkette ist Bot (AK14).
 */
final class AnfrageNachrichtRandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('kontaktformular.mailFrom', 'from@example.com');
        config()->set('kontaktformular.mailTo', 'to@example.com');
        config()->set('kontaktformular.subject', 'Test-Anfrage');
        config()->set('kontaktformular.mailBcc', null);
        config()->set('kontaktformular.fileDownloadUrl', 'http://localhost/kontaktformular/file/');
        config()->set('kontaktformular.herkunft_erfassen', false);
        config()->set('kontaktformular.feld_website', false);

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
            $table->json('herkunft')->nullable();
            $table->string('website', 2048)->nullable();
            $table->timestamps();
        });
        Mail::fake();
    }

    /**
     * @param  array<string, mixed>  $aenderungen
     * @return array<string, mixed>
     */
    private function anfrage(array $aenderungen = []): array
    {
        return array_merge([
            'Name' => 'Max Mustermann',
            'Email' => 'max@example.com',
            'Telefon' => '+49 1234 567890',
            'Nachricht' => 'Das ist eine valide Testnachricht mit genug Inhalt.',
            'datenverarbeitung' => '1',
            'site' => 'http://localhost',
        ], $aenderungen);
    }

    private function gespeicherteNachricht(): mixed
    {
        return DB::table('kontaktformular')->orderByDesc('id')->value('nachricht');
    }

    // AK13
    public function test_gespeicherte_nachricht_ist_getrimmt_in_beiden_einstellungen(): void
    {
        foreach ([true, false] as $pflicht) {
            config()->set('kontaktformular.nachricht_pflicht', $pflicht);

            // Inhalt zwischen den Rändern bleibt, auch ein Zeilenumbruch in der Mitte
            foreach ([
                "  Hallo Welt, bitte melden.\n" => 'Hallo Welt, bitte melden.',
                "\n Erste Zeile bitte\nzweite Zeile melden. \r\n" => "Erste Zeile bitte\nzweite Zeile melden.",
            ] as $roh => $erwartet) {
                $this->post(route('anfrage'), $this->anfrage(['Nachricht' => $roh]))
                    ->assertRedirect(route('danke_formular'));

                $this->assertSame($erwartet, $this->gespeicherteNachricht(), 'Pflicht '.var_export($pflicht, true));
            }
        }
    }

    // AK13
    public function test_readme_nennt_das_trimmen_der_nachricht(): void
    {
        $readme = (string) file_get_contents(dirname(__DIR__, 2).'/README.md');
        // Gegenprobe: gleiche Prüfung findet eine bestehende Aussage zur Nachricht
        $this->assertMatchesRegularExpression('/nachricht_pflicht.{0,50}KONTAKTFORMULAR_NACHRICHT_PFLICHT/su', $readme);

        $this->assertMatchesRegularExpression(
            '/Nachricht.{0,200}(?:getrimmt|Anfang und Ende)|(?:getrimmt|Anfang und Ende).{0,200}Nachricht/su',
            $readme,
            'README nennt nicht, dass Leerzeichen/Zeilenumbrüche am Anfang und Ende der Nachricht entfernt werden'
        );
    }

    // AK14
    public function test_nachricht_als_liste_oder_nicht_zeichenkette_leitet_zur_bot_seite(): void
    {
        foreach ([true, false] as $pflicht) {
            config()->set('kontaktformular.nachricht_pflicht', $pflicht);

            // Gegenprobe: gültige Nachricht wird gespeichert und verschickt
            $zeilen = DB::table('kontaktformular')->count();
            $mails = Mail::sent(Anfrage::class)->count();
            $this->post(route('anfrage'), $this->anfrage())->assertRedirect(route('danke_formular'));
            $this->assertSame($zeilen + 1, DB::table('kontaktformular')->count());
            $this->assertSame($mails + 1, Mail::sent(Anfrage::class)->count());

            $zeilen++;
            $mails++;

            foreach ([['a'], ['a', 'b'], ['x' => ['y']], []] as $wert) {
                $this->post(route('anfrage'), $this->anfrage(['Nachricht' => $wert]))
                    ->assertRedirect(route('danke_bot_formular'));

                $this->assertSame($zeilen, DB::table('kontaktformular')->count(), 'Datensatz trotz Liste');
                $this->assertSame($mails, Mail::sent(Anfrage::class)->count(), 'Mail trotz Liste');
            }
        }
    }
}
