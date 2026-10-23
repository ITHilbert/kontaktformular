<?php

declare(strict_types=1);

namespace ITHilbert\Kontaktformular\Tests\Feature;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use ITHilbert\Kontaktformular\Models\Kontaktformular;
use ITHilbert\Kontaktformular\Tests\TestCase;

/**
 * Absicherung des bestehenden Verhaltens von KontaktformularController::file_download
 * (Plan download-route, Abschnitt "Erwartetes Verhalten"; ohne Spec, daher kein AK-Verweis).
 */
final class DownloadRouteTest extends TestCase
{
    /** @var list<string> */
    private array $angelegteDateien = [];

    private bool $verzeichnisAngelegt = false;

    protected function setUp(): void
    {
        parent::setUp();

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
        foreach ($this->angelegteDateien as $pfad) {
            File::delete($pfad);
        }

        $verzeichnis = storage_path('app/kontaktformular');
        if ($this->verzeichnisAngelegt && is_dir($verzeichnis) && count(File::files($verzeichnis)) === 0) {
            File::deleteDirectory($verzeichnis);
        }

        parent::tearDown();
    }

    private function dateiPfad(string $hash): string
    {
        return storage_path('app/kontaktformular/' . $hash . '.zip');
    }

    private function anfrage(string $hash, string $name = 'anhang'): Kontaktformular
    {
        $anfrage = new Kontaktformular();
        $anfrage->fill([
            'url'               => 'http://localhost',
            'name'              => 'Test',
            'email'             => 'test@example.com',
            'nachricht'         => 'Nachricht',
            'datenverarbeitung' => true,
            'file_hash'         => $hash,
            'file_name'         => $name,
        ]);
        $anfrage->save();

        return $anfrage;
    }

    private function legeDateiAn(string $hash, string $inhalt): void
    {
        $verzeichnis = storage_path('app/kontaktformular');
        if (! is_dir($verzeichnis)) {
            File::makeDirectory($verzeichnis, 0755, true);
            $this->verzeichnisAngelegt = true;
        }

        $pfad = $this->dateiPfad($hash);
        file_put_contents($pfad, $inhalt);
        $this->angelegteDateien[] = $pfad;
    }

    private function eindeutigerHash(): string
    {
        return 'dltest' . bin2hex(random_bytes(8));
    }

    public function test_unbekannte_id_liefert_404(): void
    {
        $hash = $this->eindeutigerHash();
        $anfrage = $this->anfrage($hash);
        $this->legeDateiAn($hash, 'inhalt');

        $this->get(route('kontaktformular.file', ['hash' => $hash, 'name' => 'anhang', 'id' => $anfrage->id + 1000]))
            ->assertNotFound();
    }

    public function test_falscher_hash_liefert_404(): void
    {
        $hash = $this->eindeutigerHash();
        $anfrage = $this->anfrage($hash);
        $this->legeDateiAn($hash, 'inhalt');

        $this->get(route('kontaktformular.file', ['hash' => $hash . 'x', 'name' => 'anhang', 'id' => $anfrage->id]))
            ->assertNotFound();
    }

    public function test_falscher_name_liefert_404(): void
    {
        $hash = $this->eindeutigerHash();
        $anfrage = $this->anfrage($hash);
        $this->legeDateiAn($hash, 'inhalt');

        $this->get(route('kontaktformular.file', ['hash' => $hash, 'name' => 'anders', 'id' => $anfrage->id]))
            ->assertNotFound();
    }

    public function test_404_faelle_setzen_file_downloaded_at_nicht(): void
    {
        $hash = $this->eindeutigerHash();
        $anfrage = $this->anfrage($hash);
        $this->legeDateiAn($hash, 'inhalt');

        $this->get(route('kontaktformular.file', ['hash' => $hash . 'x', 'name' => 'anhang', 'id' => $anfrage->id]))->assertNotFound();
        $this->get(route('kontaktformular.file', ['hash' => $hash, 'name' => 'anders', 'id' => $anfrage->id]))->assertNotFound();
        $this->get(route('kontaktformular.file', ['hash' => $hash, 'name' => 'anhang', 'id' => $anfrage->id + 1000]))->assertNotFound();

        $this->assertNull($anfrage->fresh()->file_downloaded_at);
    }

    public function test_fehlende_datei_liefert_410(): void
    {
        $hash = $this->eindeutigerHash();
        $anfrage = $this->anfrage($hash);

        $this->assertFileDoesNotExist($this->dateiPfad($hash));

        $this->get(route('kontaktformular.file', ['hash' => $hash, 'name' => 'anhang', 'id' => $anfrage->id]))
            ->assertStatus(410);
    }

    public function test_fehlende_datei_setzt_file_downloaded_at_nicht(): void
    {
        $hash = $this->eindeutigerHash();
        $anfrage = $this->anfrage($hash);

        $this->get(route('kontaktformular.file', ['hash' => $hash, 'name' => 'anhang', 'id' => $anfrage->id]))
            ->assertStatus(410);

        $this->assertNull($anfrage->fresh()->file_downloaded_at);
    }

    public function test_vorhandene_datei_wird_als_name_zip_ausgeliefert(): void
    {
        $hash = $this->eindeutigerHash();
        $anfrage = $this->anfrage($hash, 'bericht');
        $this->legeDateiAn($hash, 'zip-inhalt-123');

        $response = $this->get(route('kontaktformular.file', ['hash' => $hash, 'name' => 'bericht', 'id' => $anfrage->id]));

        $response->assertOk();
        $response->assertDownload('bericht.zip');
        $this->assertStringContainsString('attachment', (string) $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('bericht.zip', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_vorhandene_datei_liefert_ihren_inhalt(): void
    {
        $hash = $this->eindeutigerHash();
        $anfrage = $this->anfrage($hash);
        $this->legeDateiAn($hash, 'zip-inhalt-123');

        $response = $this->get(route('kontaktformular.file', ['hash' => $hash, 'name' => 'anhang', 'id' => $anfrage->id]));

        $response->assertOk();
        $this->assertSame('zip-inhalt-123', $response->streamedContent());
    }

    public function test_download_setzt_file_downloaded_at(): void
    {
        $hash = $this->eindeutigerHash();
        $anfrage = $this->anfrage($hash);
        $this->legeDateiAn($hash, 'inhalt');

        $this->assertNull($anfrage->fresh()->file_downloaded_at);

        $this->get(route('kontaktformular.file', ['hash' => $hash, 'name' => 'anhang', 'id' => $anfrage->id]))->assertOk();

        $this->assertNotNull($anfrage->fresh()->file_downloaded_at);
    }

    public function test_datei_bleibt_nach_dem_download_erhalten(): void
    {
        $hash = $this->eindeutigerHash();
        $anfrage = $this->anfrage($hash);
        $this->legeDateiAn($hash, 'inhalt');

        $response = $this->get(route('kontaktformular.file', ['hash' => $hash, 'name' => 'anhang', 'id' => $anfrage->id]));
        $response->assertOk();
        $response->streamedContent();

        $this->assertFileExists($this->dateiPfad($hash));
    }
}
