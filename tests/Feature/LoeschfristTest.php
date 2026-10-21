<?php

declare(strict_types=1);

namespace ITHilbert\Kontaktformular\Tests\Feature;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use ITHilbert\Kontaktformular\Models\Kontaktformular;
use Tests\TestCase;

final class LoeschfristTest extends TestCase
{
    private string $originalStoragePath;

    private string $tempStoragePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalStoragePath = $this->app->storagePath();
        $this->tempStoragePath     = sys_get_temp_dir() . '/kontaktformular-loeschfrist-' . uniqid('', true);
        File::makeDirectory($this->tempStoragePath . '/app/kontaktformular', 0755, true);
        $this->app->useStoragePath($this->tempStoragePath);

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

        $this->travelTo(Carbon::parse('2026-10-21 12:00:00'));
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        $this->app->useStoragePath($this->originalStoragePath);
        File::deleteDirectory($this->tempStoragePath);

        parent::tearDown();
    }

    private function anfrage(int $alterInTagen, ?string $hash = null, bool $dateiAnlegen = true): Kontaktformular
    {
        $anfrage = new Kontaktformular();
        $anfrage->fill([
            'url'               => 'http://localhost',
            'name'              => 'Test ' . uniqid(),
            'email'             => 'test@example.com',
            'nachricht'         => 'Nachricht',
            'datenverarbeitung' => true,
            'file_hash'         => $hash,
            'file_name'         => $hash === null ? null : 'anhang.pdf',
        ]);
        $anfrage->created_at = now()->subDays($alterInTagen)->subMinute();
        $anfrage->updated_at = $anfrage->created_at;
        $anfrage->save();

        if ($hash !== null && $dateiAnlegen) {
            file_put_contents($this->dateiPfad($hash), 'zip-inhalt');
        }

        return $anfrage;
    }

    private function dateiPfad(string $hash): string
    {
        return storage_path('app/kontaktformular/' . $hash . '.zip');
    }

    // AK1
    public function test_config_hat_standardwerte_30_und_null(): void
    {
        $this->assertSame(30, (int) config('kontaktformular.dateien_loeschen_nach_tagen'));
        $this->assertNotNull(config('kontaktformular.dateien_loeschen_nach_tagen'));
        $this->assertTrue(config()->has('kontaktformular.anfragen_loeschen_nach_tagen'));
        $this->assertNull(config('kontaktformular.anfragen_loeschen_nach_tagen'));
    }

    // AK1
    public function test_ohne_einstellung_werden_anhaenge_aelter_als_30_tage_geloescht(): void
    {
        $alt = $this->anfrage(31, 'hash-alt');

        $this->artisan('kontaktformular:delete_old_files')->assertSuccessful();

        $this->assertFileDoesNotExist($this->dateiPfad('hash-alt'));
        $this->assertNotNull($alt->fresh()->file_deleted_at);
    }

    // AK1
    public function test_ohne_einstellung_bleiben_anhaenge_juenger_als_30_tage(): void
    {
        $jung = $this->anfrage(29, 'hash-jung');

        $this->artisan('kontaktformular:delete_old_files')->assertSuccessful();

        $this->assertFileExists($this->dateiPfad('hash-jung'));
        $this->assertNull($jung->fresh()->file_deleted_at);
    }

    // AK1
    public function test_ohne_einstellung_bleiben_alte_anfragen_erhalten(): void
    {
        $sehrAlt = $this->anfrage(1000, 'hash-sehr-alt');

        $this->artisan('kontaktformular:delete_old_files')->assertSuccessful();

        $this->assertDatabaseHas('kontaktformular', ['id' => $sehrAlt->id]);
    }

    // AK2
    public function test_loescht_anfragen_aelter_als_eingestellte_frist_samt_anhang(): void
    {
        config()->set('kontaktformular.anfragen_loeschen_nach_tagen', 60);
        $alt = $this->anfrage(61, 'hash-alt');

        $this->artisan('kontaktformular:delete_old_files')->assertSuccessful();

        $this->assertDatabaseMissing('kontaktformular', ['id' => $alt->id]);
        $this->assertFileDoesNotExist($this->dateiPfad('hash-alt'));
    }

    // AK2
    public function test_juengere_anfragen_bleiben_unveraendert(): void
    {
        config()->set('kontaktformular.anfragen_loeschen_nach_tagen', 60);
        $jung = $this->anfrage(59, 'hash-jung');
        $ohneAnhang = $this->anfrage(10);

        $this->artisan('kontaktformular:delete_old_files')->assertSuccessful();

        $this->assertDatabaseHas('kontaktformular', ['id' => $jung->id, 'file_deleted_at' => null]);
        $this->assertDatabaseHas('kontaktformular', ['id' => $ohneAnhang->id]);
        $this->assertFileExists($this->dateiPfad('hash-jung'));
    }

    // AK2
    public function test_loescht_alte_anfrage_ohne_anhang(): void
    {
        config()->set('kontaktformular.anfragen_loeschen_nach_tagen', 60);
        $alt = $this->anfrage(90);

        $this->artisan('kontaktformular:delete_old_files')->assertSuccessful();

        $this->assertDatabaseMissing('kontaktformular', ['id' => $alt->id]);
    }

    // AK2
    public function test_loescht_auch_anfrage_deren_anhang_schon_geloescht_wurde(): void
    {
        config()->set('kontaktformular.anfragen_loeschen_nach_tagen', 60);
        $alt = $this->anfrage(90, 'hash-schon-weg', false);
        $alt->file_deleted_at = now()->subDays(30);
        $alt->save();

        $this->artisan('kontaktformular:delete_old_files')->assertSuccessful();

        $this->assertDatabaseMissing('kontaktformular', ['id' => $alt->id]);
    }

    // AK3
    public function test_anhaenge_werden_nach_eigener_frist_geloescht_und_datensatz_bleibt(): void
    {
        config()->set('kontaktformular.dateien_loeschen_nach_tagen', 10);
        $alt  = $this->anfrage(11, 'hash-alt');
        $jung = $this->anfrage(9, 'hash-jung');

        $this->artisan('kontaktformular:delete_old_files')->assertSuccessful();

        $this->assertFileDoesNotExist($this->dateiPfad('hash-alt'));
        $this->assertNotNull($alt->fresh()->file_deleted_at);
        $this->assertFileExists($this->dateiPfad('hash-jung'));
        $this->assertNull($jung->fresh()->file_deleted_at);
    }

    // AK3
    public function test_laengere_dateifrist_als_standard_haelt_anhaenge_laenger(): void
    {
        config()->set('kontaktformular.dateien_loeschen_nach_tagen', 90);
        $anfrage = $this->anfrage(45, 'hash-45');

        $this->artisan('kontaktformular:delete_old_files')->assertSuccessful();

        $this->assertFileExists($this->dateiPfad('hash-45'));
        $this->assertNull($anfrage->fresh()->file_deleted_at);
    }

    // AK4
    public function test_nennt_zahl_geloeschter_anhaenge_und_anfragen_und_endet_mit_0(): void
    {
        config()->set('kontaktformular.dateien_loeschen_nach_tagen', 10);
        config()->set('kontaktformular.anfragen_loeschen_nach_tagen', 20);

        for ($i = 0; $i < 7; $i++) {
            $this->anfrage(15, 'hash-' . $i);
        }
        for ($i = 0; $i < 4; $i++) {
            $this->anfrage(25);
        }
        $this->anfrage(1);

        $this->artisan('kontaktformular:delete_old_files')
            ->expectsOutputToContain('7')
            ->expectsOutputToContain('4')
            ->assertExitCode(0);
    }

    // Randfall: Anhang-Datei fehlt bereits
    // AK3
    public function test_fehlende_anhang_datei_ist_kein_fehler_und_datensatz_wird_behandelt(): void
    {
        $anfrage = $this->anfrage(40, 'hash-fehlt', false);

        $this->artisan('kontaktformular:delete_old_files')->assertSuccessful();

        $this->assertNotNull($anfrage->fresh()->file_deleted_at);
    }

    // Randfall: Anhang-Datei fehlt bereits
    // AK2
    public function test_fehlende_anhang_datei_verhindert_loeschen_der_anfrage_nicht(): void
    {
        config()->set('kontaktformular.anfragen_loeschen_nach_tagen', 60);
        $anfrage = $this->anfrage(90, 'hash-fehlt', false);

        $this->artisan('kontaktformular:delete_old_files')->assertSuccessful();

        $this->assertDatabaseMissing('kontaktformular', ['id' => $anfrage->id]);
    }

    // Randfall: Anfragenfrist kleiner als Dateifrist
    // AK2
    public function test_kuerzere_anfragenfrist_loescht_anfrage_samt_anhang_vor_der_dateifrist(): void
    {
        config()->set('kontaktformular.dateien_loeschen_nach_tagen', 30);
        config()->set('kontaktformular.anfragen_loeschen_nach_tagen', 10);
        $anfrage = $this->anfrage(15, 'hash-kurz');

        $this->artisan('kontaktformular:delete_old_files')->assertSuccessful();

        $this->assertDatabaseMissing('kontaktformular', ['id' => $anfrage->id]);
        $this->assertFileDoesNotExist($this->dateiPfad('hash-kurz'));
    }

    /**
     * @return array<string, array{string, mixed}>
     */
    public static function ungueltigeWerte(): array
    {
        return [
            'dateien null'      => ['dateien_loeschen_nach_tagen', 0],
            'dateien negativ'   => ['dateien_loeschen_nach_tagen', -5],
            'dateien text'      => ['dateien_loeschen_nach_tagen', 'abc'],
            'dateien kommazahl' => ['dateien_loeschen_nach_tagen', 1.5],
            'anfragen null'     => ['anfragen_loeschen_nach_tagen', 0],
            'anfragen negativ'  => ['anfragen_loeschen_nach_tagen', -5],
            'anfragen text'     => ['anfragen_loeschen_nach_tagen', 'abc'],
            'anfragen kommazahl' => ['anfragen_loeschen_nach_tagen', 1.5],
        ];
    }

    // Randfall: ungültiger Wert
    // AK4
    #[\PHPUnit\Framework\Attributes\DataProvider('ungueltigeWerte')]
    public function test_ungueltiger_wert_bricht_ab_und_loescht_nichts(string $schluessel, mixed $wert): void
    {
        config()->set('kontaktformular.' . $schluessel, $wert);
        $alt = $this->anfrage(500, 'hash-alt');

        $this->artisan('kontaktformular:delete_old_files')->assertFailed();

        $this->assertDatabaseHas('kontaktformular', ['id' => $alt->id, 'file_deleted_at' => null]);
        $this->assertFileExists($this->dateiPfad('hash-alt'));
    }
}
