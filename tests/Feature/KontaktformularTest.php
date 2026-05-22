<?php

declare(strict_types=1);

namespace ITHilbert\Kontaktformular\Tests\Feature;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use ITHilbert\Kontaktformular\Mail\Anfrage;
use ITHilbert\Kontaktformular\Models\Kontaktformular;
use Tests\TestCase;

final class KontaktformularTest extends TestCase
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

    public function test_anfrage_ohne_telefon_wird_akzeptiert(): void
    {
        Mail::fake();

        $response = $this->post(route('anfrage'), [
            'Name'              => 'Max Mustermann',
            'Email'             => 'max@example.com',
            'Nachricht'         => 'Das ist eine valide Testnachricht mit genug Inhalt.',
            'datenverarbeitung' => '1',
            'site'              => 'http://localhost',
        ]);

        $response->assertRedirect(route('danke_formular'));

        $this->assertDatabaseHas('kontaktformular', [
            'email'   => 'max@example.com',
            'name'    => 'Max Mustermann',
            'telefon' => null,
        ]);

        Mail::assertSent(Anfrage::class);
    }

    public function test_anfrage_mit_telefon_speichert_telefon(): void
    {
        Mail::fake();

        $response = $this->post(route('anfrage'), [
            'Name'              => 'Erika Musterfrau',
            'Email'             => 'erika@example.com',
            'Telefon'           => '+49 1234 567890',
            'Nachricht'         => 'Eine weitere valide Testnachricht mit Spaces.',
            'datenverarbeitung' => '1',
            'site'              => 'http://localhost',
        ]);

        $response->assertRedirect(route('danke_formular'));

        $this->assertDatabaseHas('kontaktformular', [
            'email'   => 'erika@example.com',
            'telefon' => '+49 1234 567890',
        ]);
    }

    public function test_honeypot_leitet_auf_bot_danke_seite(): void
    {
        Mail::fake();

        $response = $this->post(route('anfrage'), [
            'Name'              => 'Bot',
            'Email'             => 'bot@example.com',
            'Nachricht'         => 'Spam-Nachricht ist lang genug fuer den Filter.',
            'datenverarbeitung' => '1',
            'site'              => 'http://localhost',
            'fax'               => 'gefuellt',
        ]);

        $response->assertRedirect(route('danke_bot_formular'));
        $this->assertDatabaseCount('kontaktformular', 0);
        Mail::assertNothingSent();
    }

    public function test_kurze_nachricht_wird_als_bot_behandelt(): void
    {
        Mail::fake();

        $response = $this->post(route('anfrage'), [
            'Name'              => 'Spam',
            'Email'             => 'spam@example.com',
            'Nachricht'         => 'kurz',
            'datenverarbeitung' => '1',
            'site'              => 'http://localhost',
        ]);

        $response->assertRedirect(route('danke_bot_formular'));
        $this->assertDatabaseCount('kontaktformular', 0);
        Mail::assertNothingSent();
    }

    public function test_validation_schlaegt_ohne_pflichtfelder_fehl(): void
    {
        $response = $this->postJson(route('anfrage'), [
            'Nachricht'         => 'Eine gueltige Testnachricht mit Leerzeichen.',
            'datenverarbeitung' => '1',
            'site'              => 'http://localhost',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['Name', 'Email']);
        $response->assertJsonMissingValidationErrors(['Telefon']);
    }

    public function test_nummer_zaehlt_pro_jahr_hoch(): void
    {
        $erster = new Kontaktformular();
        $erster->fill([
            'url'               => 'http://localhost',
            'name'              => 'A',
            'email'             => 'a@example.com',
            'nachricht'         => 'erste',
            'datenverarbeitung' => true,
        ]);
        $erster->save();

        $zweiter = new Kontaktformular();
        $zweiter->fill([
            'url'               => 'http://localhost',
            'name'              => 'B',
            'email'             => 'b@example.com',
            'nachricht'         => 'zweite',
            'datenverarbeitung' => true,
        ]);
        $zweiter->save();

        $this->assertSame((int) date('Y'), $erster->jahr);
        $this->assertSame(1, $erster->nummer);
        $this->assertSame(2, $zweiter->nummer);
    }
}
