<?php

declare(strict_types=1);

namespace ITHilbert\Kontaktformular\Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use ITHilbert\Kontaktformular\Models\Kontaktformular;
use ITHilbert\Kontaktformular\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use ZipArchive;

/**
 * Anhänge über die Storage-Disk (Feature anhaenge-disk, Dashboard #119).
 * Die Disk `local` wird auf ein temporäres Verzeichnis gelegt (Root `app/private` bzw. `app`).
 */
final class AnhaengeDiskTest extends TestCase
{
    private string $originalStoragePath;

    private string $temp;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalStoragePath = $this->app->storagePath();
        $this->temp = sys_get_temp_dir().'/kontaktformular-anhaenge-disk-'.uniqid('', true);
        File::makeDirectory($this->temp.'/app/private', 0755, true);
        $this->app->useStoragePath($this->temp);

        $this->diskRoot('app/private');

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
            $table->timestamps();
        });

        $this->travelTo(Carbon::parse('2026-10-31 12:00:00'));
        Mail::fake();
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        $this->app->useStoragePath($this->originalStoragePath);
        File::deleteDirectory($this->temp);

        parent::tearDown();
    }

    /** Legt die Disk `local` auf `<temp>/<unterordner>`. */
    private function diskRoot(string $unterordner): void
    {
        File::ensureDirectoryExists($this->temp.'/'.$unterordner);
        config()->set('filesystems.disks.local.root', $this->temp.'/'.$unterordner);
        Storage::forgetDisk('local');
    }

    private function eigeneDisk(): void
    {
        File::ensureDirectoryExists($this->temp.'/eigene');
        config()->set('filesystems.disks.anhaenge', ['driver' => 'local', 'root' => $this->temp.'/eigene']);
        config()->set('kontaktformular.disk', 'anhaenge');
        Storage::forgetDisk('anhaenge');
    }

    private function upload(UploadedFile $datei): Kontaktformular
    {
        $this->post(route('anfrage'), [
            'Name' => 'Max Mustermann',
            'Email' => 'max@example.com',
            'Nachricht' => 'Das ist eine valide Testnachricht mit genug Inhalt.',
            'datenverarbeitung' => '1',
            'site' => 'http://localhost',
            'Datei' => $datei,
        ])->assertRedirect(route('danke_formular'));

        return Kontaktformular::query()->latest('id')->firstOrFail();
    }

    private function pdf(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('Mein Bericht (1).pdf', str_repeat('a', 524288));
    }

    private function link(Kontaktformular $anfrage): string
    {
        return route('kontaktformular.file', ['hash' => $anfrage->file_hash, 'name' => $anfrage->file_name, 'id' => $anfrage->id]);
    }

    private function anfrage(int $alterInTagen, string $hash): Kontaktformular
    {
        $anfrage = new Kontaktformular;
        $anfrage->fill([
            'url' => 'http://localhost',
            'name' => 'Test '.uniqid(),
            'email' => 'test@example.com',
            'nachricht' => 'Nachricht',
            'datenverarbeitung' => true,
            'file_hash' => $hash,
            'file_name' => 'anhang',
        ]);
        $anfrage->created_at = now()->subDays($alterInTagen)->subMinute();
        $anfrage->updated_at = $anfrage->created_at;
        $anfrage->save();

        return $anfrage;
    }

    /** Legt einen Anhang unter dem Disk-Root ab (Pfad relativ zu `<temp>/<root>`). */
    private function anhangAblegen(string $root, string $hash): string
    {
        $verzeichnis = $this->temp.'/'.$root.'/kontaktformular';
        File::ensureDirectoryExists($verzeichnis);
        $pfad = $verzeichnis.'/'.$hash.'.zip';
        file_put_contents($pfad, 'zip-inhalt-'.$hash);

        return $pfad;
    }

    // ---------------------------------------------------------------- AK1

    // AK1
    public function test_config_hat_standardwerte_disk_local_und_einplanen_true(): void
    {
        $this->assertTrue(config()->has('kontaktformular.disk'));
        $this->assertSame('local', config('kontaktformular.disk'));
        $this->assertTrue(config()->has('kontaktformular.loeschen_einplanen'));
        $this->assertTrue((bool) config('kontaktformular.loeschen_einplanen'));
    }

    // AK1 (Speichern und Zippen)
    public function test_upload_nutzt_die_konfigurierte_disk_und_nicht_die_standard_disk(): void
    {
        $this->eigeneDisk();

        $anfrage = $this->upload($this->pdf());

        $this->assertFileExists($this->temp.'/eigene/kontaktformular/'.$anfrage->file_hash.'.zip');
        $this->assertSame(['kontaktformular/'.$anfrage->file_hash.'.zip'], Storage::disk('anhaenge')->allFiles());
        $this->assertSame([], File::allFiles($this->temp.'/app'));
        $this->assertDirectoryDoesNotExist($this->temp.'/app/kontaktformular');
    }

    // AK1 (Ausliefern)
    public function test_download_nutzt_die_konfigurierte_disk(): void
    {
        $this->eigeneDisk();
        $anfrage = $this->upload($this->pdf());
        $this->assertFileExists($this->temp.'/eigene/kontaktformular/'.$anfrage->file_hash.'.zip');

        $this->get($this->link($anfrage))->assertOk()->assertDownload('MeinBericht1.zip');
        $this->assertNotNull($anfrage->fresh()->file_downloaded_at);
    }

    // AK1 (Ausliefern, Gegenprobe: Datei nur auf der Standard-Disk zählt nicht)
    public function test_download_ignoriert_dateien_auf_der_standard_disk_wenn_andere_disk_konfiguriert(): void
    {
        $this->eigeneDisk();
        $anfrage = $this->anfrage(1, 'nur-auf-local');
        $this->anhangAblegen('app/private', 'nur-auf-local');
        $this->anhangAblegen('app', 'nur-auf-local');
        $this->assertFileDoesNotExist($this->temp.'/eigene/kontaktformular/nur-auf-local.zip');

        $this->get($this->link($anfrage))->assertStatus(410);
    }

    // AK1 (Löschen)
    public function test_loeschen_nutzt_die_konfigurierte_disk(): void
    {
        $this->eigeneDisk();
        File::ensureDirectoryExists($this->temp.'/eigene/kontaktformular');
        file_put_contents($this->temp.'/eigene/kontaktformular/hash-alt.zip', 'x');
        file_put_contents($this->temp.'/eigene/kontaktformular/hash-jung.zip', 'x');
        $lokal = $this->anhangAblegen('app/private', 'hash-alt');
        $alt = $this->anfrage(31, 'hash-alt');
        $this->anfrage(5, 'hash-jung');

        $this->artisan('kontaktformular:delete_old_files')->assertSuccessful();

        $this->assertFileDoesNotExist($this->temp.'/eigene/kontaktformular/hash-alt.zip');
        $this->assertFileExists($this->temp.'/eigene/kontaktformular/hash-jung.zip');
        $this->assertFileExists($lokal, 'Die Standard-Disk bleibt unberührt');
        $this->assertNotNull($alt->fresh()->file_deleted_at);
    }

    // ---------------------------------------------------------------- AK2

    // AK2
    public function test_upload_legt_zip_auf_der_disk_unter_kontaktformular_hash_zip_ab(): void
    {
        $anfrage = $this->upload($this->pdf());

        $this->assertNotEmpty($anfrage->file_hash);
        $this->assertFileExists($this->temp.'/app/private/kontaktformular/'.$anfrage->file_hash.'.zip');
        $this->assertTrue(Storage::disk('local')->exists('kontaktformular/'.$anfrage->file_hash.'.zip'));
        $this->assertDirectoryDoesNotExist($this->temp.'/app/kontaktformular');
    }

    // AK2
    public function test_zip_enthaelt_die_datei_unter_ihrem_bereinigten_namen(): void
    {
        $anfrage = $this->upload($this->pdf());

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($this->temp.'/app/private/kontaktformular/'.$anfrage->file_hash.'.zip') === true);
        $this->assertSame(1, $zip->numFiles);
        $this->assertSame('MeinBericht1.pdf', $zip->getNameIndex(0));
        $this->assertSame(str_repeat('a', 524288), $zip->getFromName('MeinBericht1.pdf'));
        $zip->close();
    }

    // AK2
    public function test_unverpackte_datei_ist_entfernt(): void
    {
        $anfrage = $this->upload($this->pdf());

        $this->assertFileExists($this->temp.'/app/private/kontaktformular/'.$anfrage->file_hash.'.zip');
        $this->assertSame(
            ['kontaktformular/'.$anfrage->file_hash.'.zip'],
            Storage::disk('local')->allFiles(),
            'Auf der Disk liegt nur das ZIP, keine unverpackte Datei'
        );
    }

    // AK2
    public function test_upload_setzt_file_felder_wie_bisher(): void
    {
        $anfrage = $this->upload($this->pdf());

        $this->assertSame('MeinBericht1', $anfrage->file_name);
        $this->assertSame('pdf', $anfrage->file_type);
        $this->assertSame(0.5, (float) $anfrage->file_size);
        $this->assertFileExists($this->temp.'/app/private/kontaktformular/'.$anfrage->file_hash.'.zip');
        $this->assertStringNotContainsString('.', (string) $anfrage->file_hash);
    }

    // ---------------------------------------------------------------- AK3

    // AK3
    public function test_download_antwortet_200_mit_zip_als_name_zip(): void
    {
        $anfrage = $this->upload($this->pdf());
        $this->assertFileExists($this->temp.'/app/private/kontaktformular/'.$anfrage->file_hash.'.zip');

        $response = $this->get($this->link($anfrage));

        $response->assertOk();
        $response->assertDownload('MeinBericht1.zip');
        $this->assertSame(
            file_get_contents($this->temp.'/app/private/kontaktformular/'.$anfrage->file_hash.'.zip'),
            $response->streamedContent()
        );
    }

    // AK3
    public function test_download_setzt_file_downloaded_at(): void
    {
        $anfrage = $this->upload($this->pdf());
        $this->assertNull($anfrage->fresh()->file_downloaded_at);

        $this->get($this->link($anfrage))->assertOk();

        $this->assertNotNull($anfrage->fresh()->file_downloaded_at);
    }

    // AK3
    public function test_fehlt_die_datei_auf_der_disk_antwortet_die_route_mit_410(): void
    {
        $anfrage = $this->upload($this->pdf());
        $this->get($this->link($anfrage))->assertOk();
        Kontaktformular::whereKey($anfrage->id)->update(['file_downloaded_at' => null]);

        Storage::disk('local')->delete('kontaktformular/'.$anfrage->file_hash.'.zip');

        $this->get($this->link($anfrage))->assertStatus(410);
        $this->assertNull($anfrage->fresh()->file_downloaded_at);
    }

    // AK3
    public function test_falscher_hash_name_oder_id_liefern_404_und_setzen_nichts(): void
    {
        $anfrage = $this->upload($this->pdf());
        $this->get($this->link($anfrage))->assertOk();
        Kontaktformular::whereKey($anfrage->id)->update(['file_downloaded_at' => null]);

        $this->get(route('kontaktformular.file', ['hash' => $anfrage->file_hash.'x', 'name' => $anfrage->file_name, 'id' => $anfrage->id]))->assertNotFound();
        $this->get(route('kontaktformular.file', ['hash' => $anfrage->file_hash, 'name' => 'anders', 'id' => $anfrage->id]))->assertNotFound();
        $this->get(route('kontaktformular.file', ['hash' => $anfrage->file_hash, 'name' => $anfrage->file_name, 'id' => $anfrage->id + 1000]))->assertNotFound();

        $this->assertNull($anfrage->fresh()->file_downloaded_at);
    }

    // ---------------------------------------------------------------- AK4

    // AK4
    public function test_loeschen_entfernt_alten_anhang_von_der_disk_und_setzt_file_deleted_at(): void
    {
        $pfad = $this->anhangAblegen('app/private', 'hash-alt');
        $alt = $this->anfrage(31, 'hash-alt');
        $this->assertFileExists($pfad);
        $this->assertNull($alt->file_deleted_at);

        $this->artisan('kontaktformular:delete_old_files')->assertSuccessful();

        $this->assertFileDoesNotExist($pfad);
        $this->assertFalse(Storage::disk('local')->exists('kontaktformular/hash-alt.zip'));
        $this->assertNotNull($alt->fresh()->file_deleted_at);
    }

    // AK4
    public function test_juengere_anhaenge_bleiben(): void
    {
        $altPfad = $this->anhangAblegen('app/private', 'hash-alt');
        $jungPfad = $this->anhangAblegen('app/private', 'hash-jung');
        $this->anfrage(31, 'hash-alt');
        $jung = $this->anfrage(29, 'hash-jung');

        $this->artisan('kontaktformular:delete_old_files')->assertSuccessful();

        $this->assertFileDoesNotExist($altPfad);
        $this->assertFileExists($jungPfad);
        $this->assertNull($jung->fresh()->file_deleted_at);
    }

    // AK4
    public function test_anfragen_nach_anfragenfrist_werden_samt_anhang_geloescht(): void
    {
        config()->set('kontaktformular.anfragen_loeschen_nach_tagen', 60);
        $altPfad = $this->anhangAblegen('app/private', 'hash-alt');
        $jungPfad = $this->anhangAblegen('app/private', 'hash-jung');
        $alt = $this->anfrage(61, 'hash-alt');
        $jung = $this->anfrage(10, 'hash-jung');

        $this->artisan('kontaktformular:delete_old_files')->assertSuccessful();

        $this->assertDatabaseMissing('kontaktformular', ['id' => $alt->id]);
        $this->assertFileDoesNotExist($altPfad);
        $this->assertDatabaseHas('kontaktformular', ['id' => $jung->id]);
        $this->assertFileExists($jungPfad);
    }

    // Randfall: Anhang-Datei fehlt beim Löschen bereits
    // AK4
    public function test_fehlende_anhang_datei_ist_kein_fehler_und_datensatz_wird_behandelt(): void
    {
        $vorhanden = $this->anhangAblegen('app/private', 'hash-da');
        $this->anfrage(31, 'hash-da');
        $fehlt = $this->anfrage(40, 'hash-fehlt');
        $this->assertFileExists($vorhanden);
        $this->assertFalse(Storage::disk('local')->exists('kontaktformular/hash-fehlt.zip'));

        $this->artisan('kontaktformular:delete_old_files')->assertSuccessful();

        $this->assertNotNull($fehlt->fresh()->file_deleted_at);
        $this->assertFileDoesNotExist($vorhanden);
    }

    // Randfall: Anhang-Datei fehlt beim Löschen bereits (Anfragenfrist)
    // AK4
    public function test_fehlende_anhang_datei_verhindert_loeschen_der_anfrage_nicht(): void
    {
        config()->set('kontaktformular.anfragen_loeschen_nach_tagen', 60);
        $vorhanden = $this->anhangAblegen('app/private', 'hash-da');
        $this->anfrage(90, 'hash-da');
        $fehlt = $this->anfrage(90, 'hash-fehlt');
        $this->assertFileExists($vorhanden);

        $this->artisan('kontaktformular:delete_old_files')->assertSuccessful();

        $this->assertDatabaseMissing('kontaktformular', ['id' => $fehlt->id]);
        $this->assertFileDoesNotExist($vorhanden);
    }

    // Randfall: wiederholbar
    // AK4
    public function test_command_ist_wiederholbar(): void
    {
        $pfad = $this->anhangAblegen('app/private', 'hash-alt');
        $alt = $this->anfrage(31, 'hash-alt');

        $this->artisan('kontaktformular:delete_old_files')->assertSuccessful();
        $this->assertFileDoesNotExist($pfad);
        $this->assertNotNull($alt->fresh()->file_deleted_at);

        $this->artisan('kontaktformular:delete_old_files')->assertSuccessful();
        $this->assertNotNull($alt->fresh()->file_deleted_at);
    }

    // ---------------------------------------------------------------- AK5

    // AK5
    public function test_bei_root_storage_app_liegen_neue_anhaenge_unter_storage_app_kontaktformular(): void
    {
        $this->diskRoot('app');

        $anfrage = $this->upload($this->pdf());

        $this->assertFileExists($this->temp.'/app/kontaktformular/'.$anfrage->file_hash.'.zip');
        $this->assertFileDoesNotExist($this->temp.'/app/private/kontaktformular/'.$anfrage->file_hash.'.zip');
        $this->assertSame(['kontaktformular/'.$anfrage->file_hash.'.zip'], Storage::disk('local')->allFiles());
        $this->assertSame('MeinBericht1', $anfrage->file_name);
        $this->assertSame('pdf', $anfrage->file_type);
    }

    // AK5
    public function test_vorhandener_anhang_bei_root_storage_app_ist_ueber_seinen_link_herunterladbar(): void
    {
        $this->diskRoot('app');
        $pfad = $this->anhangAblegen('app', 'hash-bestand');
        $anfrage = $this->anfrage(1, 'hash-bestand');
        $this->assertFileExists($pfad);

        $response = $this->get($this->link($anfrage));

        $response->assertOk();
        $response->assertDownload('anhang.zip');
        $this->assertSame('zip-inhalt-hash-bestand', $response->streamedContent());
        $this->assertNotNull($anfrage->fresh()->file_downloaded_at);
    }

    // AK5
    public function test_vorhandener_anhang_bei_root_storage_app_wird_nach_ablauf_der_frist_geloescht(): void
    {
        $this->diskRoot('app');
        $altPfad = $this->anhangAblegen('app', 'hash-alt');
        $jungPfad = $this->anhangAblegen('app', 'hash-jung');
        $alt = $this->anfrage(31, 'hash-alt');
        $jung = $this->anfrage(5, 'hash-jung');

        $this->artisan('kontaktformular:delete_old_files')->assertSuccessful();

        $this->assertFileDoesNotExist($altPfad);
        $this->assertNotNull($alt->fresh()->file_deleted_at);
        $this->assertFileExists($jungPfad);
        $this->assertNull($jung->fresh()->file_deleted_at);
    }

    // AK5
    public function test_bei_root_storage_app_werden_anfragen_nach_anfragenfrist_samt_anhang_geloescht(): void
    {
        $this->diskRoot('app');
        config()->set('kontaktformular.anfragen_loeschen_nach_tagen', 60);
        $pfad = $this->anhangAblegen('app', 'hash-alt');
        $alt = $this->anfrage(61, 'hash-alt');

        $this->artisan('kontaktformular:delete_old_files')->assertSuccessful();

        $this->assertDatabaseMissing('kontaktformular', ['id' => $alt->id]);
        $this->assertFileDoesNotExist($pfad);
    }

    // ---------------------------------------------------------------- Randfall ZIP-Upload

    // Randfall: Upload ist selbst ein ZIP
    // AK2
    public function test_zip_upload_wird_nicht_erneut_gepackt_und_liegt_als_hash_zip_auf_der_disk(): void
    {
        $inhalt = 'PK-eigener-zip-inhalt';
        $anfrage = $this->upload(UploadedFile::fake()->createWithContent('archiv.zip', $inhalt));

        $this->assertSame('zip', $anfrage->file_type);
        $this->assertSame('archiv', $anfrage->file_name);
        $this->assertFileExists($this->temp.'/app/private/kontaktformular/'.$anfrage->file_hash.'.zip');
        $this->assertSame($inhalt, file_get_contents($this->temp.'/app/private/kontaktformular/'.$anfrage->file_hash.'.zip'));
        $this->assertSame(['kontaktformular/'.$anfrage->file_hash.'.zip'], Storage::disk('local')->allFiles());
    }

    // Randfall: Upload ist selbst ein ZIP (Bestand-Root)
    // AK5
    public function test_zip_upload_bei_root_storage_app_liegt_unter_storage_app_kontaktformular(): void
    {
        $this->diskRoot('app');
        $anfrage = $this->upload(UploadedFile::fake()->createWithContent('archiv.zip', 'PK-x'));

        $this->assertSame('PK-x', file_get_contents($this->temp.'/app/kontaktformular/'.$anfrage->file_hash.'.zip'));
    }

    // ---------------------------------------------------------------- AK6

    private function loeschEvents(): int
    {
        $this->app->forgetInstance(Schedule::class);
        $schedule = $this->app->make(Schedule::class);

        return count(array_filter(
            $schedule->events(),
            static fn ($event): bool => str_contains((string) $event->command, 'kontaktformular:delete_old_files')
        ));
    }

    // AK6
    public function test_standardmaessig_ist_der_command_eingeplant(): void
    {
        $this->assertSame(1, $this->loeschEvents());
    }

    // AK6
    public function test_command_ist_taeglich_eingeplant(): void
    {
        $this->app->forgetInstance(Schedule::class);
        $events = array_values(array_filter(
            $this->app->make(Schedule::class)->events(),
            static fn ($event): bool => str_contains((string) $event->command, 'kontaktformular:delete_old_files')
        ));

        $this->assertCount(1, $events);
        $this->assertMatchesRegularExpression('/^\d+ \d+ \* \* \*$/', $events[0]->expression);
    }

    // AK6
    public function test_bei_true_ist_der_command_eingeplant(): void
    {
        config()->set('kontaktformular.loeschen_einplanen', true);

        $this->assertSame(1, $this->loeschEvents());
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function ausgeschalteteWerte(): array
    {
        return [
            'bool false' => [false],
            'string false' => ['false'],
            'string 0' => ['0'],
            'string off' => ['off'],
            'leer gesetzt' => [''],
        ];
    }

    // AK6 + Randfall: String-Werte werden als Bool gewertet
    #[DataProvider('ausgeschalteteWerte')]
    public function test_bei_false_ist_der_command_nicht_eingeplant(mixed $wert): void
    {
        config()->set('kontaktformular.loeschen_einplanen', true);
        $this->assertSame(1, $this->loeschEvents(), 'Gegenprobe: mit true eingeplant');

        config()->set('kontaktformular.loeschen_einplanen', $wert);

        $this->assertSame(0, $this->loeschEvents());
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function eingeschalteteStringWerte(): array
    {
        return [
            'string true' => ['true'],
            'string 1' => ['1'],
            'string on' => ['on'],
        ];
    }

    // Randfall: String-Werte werden als Bool gewertet
    // AK6
    #[DataProvider('eingeschalteteStringWerte')]
    public function test_string_wahr_werte_planen_den_command_ein(mixed $wert): void
    {
        config()->set('kontaktformular.loeschen_einplanen', false);
        $this->assertSame(0, $this->loeschEvents(), 'Gegenprobe: mit false nicht eingeplant');

        config()->set('kontaktformular.loeschen_einplanen', $wert);

        $this->assertSame(1, $this->loeschEvents());
    }
}
