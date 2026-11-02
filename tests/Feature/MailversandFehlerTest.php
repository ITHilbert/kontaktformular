<?php

declare(strict_types=1);

namespace ITHilbert\Kontaktformular\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use ITHilbert\Kontaktformular\Mail\Anfrage;
use ITHilbert\Kontaktformular\Models\Kontaktformular;
use ITHilbert\Kontaktformular\Tests\TestCase;
use Monolog\Handler\TestHandler;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Exception\RfcComplianceException;
use Throwable;

/**
 * Versandfehler nach gespeicherter Anfrage (Feature mailversand, Dashboard #316).
 * Der Mailer `wirft` läuft über den echten Versandweg und wirft beim Senden die gesetzte Ausnahme.
 */
final class MailversandFehlerTest extends TestCase
{
    private const MELDUNG = 'Versand kaputt: 535 Authentication failed';

    private string $originalStoragePath;

    private string $temp;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('kontaktformular.mailFrom', 'from@example.com');
        config()->set('kontaktformular.mailTo', 'to@example.com');
        config()->set('kontaktformular.mailBcc', null);
        config()->set('kontaktformular.subject', 'Test-Anfrage');
        config()->set('kontaktformular.fileDownloadUrl', 'http://localhost/kontaktformular/file/');

        $this->originalStoragePath = $this->app->storagePath();
        $this->temp = sys_get_temp_dir().'/kontaktformular-mailversand-'.uniqid('', true);
        File::makeDirectory($this->temp.'/app/private', 0755, true);
        $this->app->useStoragePath($this->temp);
        config()->set('filesystems.disks.local.root', $this->temp.'/app/private');
        Storage::forgetDisk('local');

        // Log in einen Speicher-Handler, um Einträge samt Kontext zu prüfen
        config()->set('logging.channels.mailtest', ['driver' => 'monolog', 'handler' => TestHandler::class]);
        config()->set('logging.default', 'mailtest');

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
    }

    protected function tearDown(): void
    {
        $this->app->useStoragePath($this->originalStoragePath);
        File::deleteDirectory($this->temp);

        parent::tearDown();
    }

    /** Mailer, der beim Senden die Ausnahme wirft (echter Versandweg Mail::to()->send()). */
    private function mailerWirft(Throwable $ausnahme): void
    {
        Mail::extend('wirft', static fn (): AbstractTransport => new WerfenderTransport($ausnahme));
        config()->set('mail.mailers.wirft', ['transport' => 'wirft']);
        config()->set('mail.default', 'wirft');
        Mail::purge('wirft');
    }

    /**
     * @return array<string, mixed>
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

    /** @return array<string, array{0: Throwable}> */
    public static function andereAusnahmen(): array
    {
        return [
            'ungültige Adresse' => [new RfcComplianceException('Email "kein-email" does not comply with addr-spec of RFC 2822.')],
            'Laufzeitfehler' => [new RuntimeException('Irgendein Fehler im Mailer')],
        ];
    }

    /** Vorbelegte Anfragen, damit id (62) und nummer (3) der neuen Anfrage verschieden sind. */
    private function vorhandeneAnfragenAnlegen(): void
    {
        $zeile = [
            'url' => 'http://localhost', 'name' => 'Alt', 'email' => 'alt@example.com',
            'nachricht' => 'alte Nachricht', 'datenverarbeitung' => true,
            'created_at' => now(), 'updated_at' => now(),
        ];
        $this->app['db']->table('kontaktformular')->insert([
            ['id' => 60, 'jahr' => (int) date('Y'), 'nummer' => 1] + $zeile,
            ['id' => 61, 'jahr' => (int) date('Y'), 'nummer' => 2] + $zeile,
        ]);
    }

    private function logHandler(): TestHandler
    {
        /** @var TestHandler */
        return Log::channel('mailtest')->getLogger()->getHandlers()[0];
    }

    /**
     * Alle Log-Einträge als Text: Meldung, Kontext (ohne Ausnahme-Objekt) und Extra.
     *
     * @return list<string>
     */
    private function logTexte(): array
    {
        $texte = [];
        foreach ($this->logHandler()->getRecords() as $eintrag) {
            $kontext = $eintrag->context;
            $ausnahme = $kontext['exception'] ?? null;
            unset($kontext['exception']);
            $texte[] = $eintrag->message.' | '.($ausnahme instanceof Throwable ? $ausnahme->getMessage() : '')
                .' | '.json_encode($kontext, JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_UNESCAPED_UNICODE)
                .' | '.json_encode($eintrag->extra, JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_UNESCAPED_UNICODE);
        }

        return $texte;
    }

    private function nenntAnfrage(string $text, int $id, int $nummer): bool
    {
        return preg_match('/(?<!\d)'.$id.'(?!\d)/', $text) === 1
            || preg_match('/nummer\D{0,12}'.$nummer.'(?!\d)/i', $text) === 1
            || preg_match('/\d{4}[\/-]0*'.$nummer.'(?!\d)/', $text) === 1;
    }

    // ---------------------------------------------------------------- AK1

    // AK1
    public function test_transportfehler_leitet_auf_danke_formular_weiter(): void
    {
        $this->mailerWirft(new TransportException(self::MELDUNG));

        $this->post(route('anfrage'), $this->gueltigeAnfrage())
            ->assertRedirect(route('danke_formular'));
    }

    // ---------------------------------------------------------------- AK2

    // AK2
    public function test_anfrage_ist_trotz_versandfehler_genau_einmal_mit_formularwerten_gespeichert(): void
    {
        $this->mailerWirft(new TransportException(self::MELDUNG));

        $this->post(route('anfrage'), $this->gueltigeAnfrage())
            ->assertRedirect(route('danke_formular'));

        $this->assertDatabaseCount('kontaktformular', 1);
        $this->assertDatabaseHas('kontaktformular', [
            'name' => 'Max Mustermann',
            'email' => 'max@example.com',
            'telefon' => '+49 1234 567890',
            'nachricht' => 'Das ist eine valide Testnachricht mit genug Inhalt.',
            'url' => 'http://localhost',
        ]);
    }

    // AK2
    public function test_anhang_liegt_trotz_versandfehler_genau_einmal_in_der_ablage(): void
    {
        $this->mailerWirft(new TransportException(self::MELDUNG));

        $this->post(route('anfrage'), array_merge($this->gueltigeAnfrage(), [
            'Datei' => UploadedFile::fake()->createWithContent('Bericht.pdf', str_repeat('a', 2048)),
        ]))->assertRedirect(route('danke_formular'));

        $anfrage = Kontaktformular::query()->firstOrFail();
        $this->assertNotNull($anfrage->file_hash);
        $this->assertDatabaseCount('kontaktformular', 1);
        $this->assertSame(
            ['kontaktformular/'.$anfrage->file_hash.'.zip'],
            Storage::disk('local')->allFiles()
        );
    }

    // ---------------------------------------------------------------- AK3

    // AK3
    public function test_ausnahme_wird_mit_ihrer_meldung_im_log_gemeldet(): void
    {
        $this->mailerWirft(new TransportException(self::MELDUNG));

        $this->post(route('anfrage'), $this->gueltigeAnfrage())->assertRedirect(route('danke_formular'));

        $gemeldet = array_filter(
            $this->logTexte(),
            static fn (string $text): bool => str_contains($text, self::MELDUNG)
        );
        $this->assertNotEmpty($gemeldet, 'Die Ausnahme wurde nicht mit ihrer Meldung gemeldet.');
    }

    // AK3
    public function test_log_eintrag_nennt_id_oder_nummer_der_gespeicherten_anfrage(): void
    {
        $this->vorhandeneAnfragenAnlegen();
        $this->mailerWirft(new TransportException(self::MELDUNG));

        $this->post(route('anfrage'), $this->gueltigeAnfrage())->assertRedirect(route('danke_formular'));

        $anfrage = Kontaktformular::query()->where('name', 'Max Mustermann')->firstOrFail();
        $this->assertSame(62, $anfrage->id);
        $this->assertSame(3, $anfrage->nummer);

        $passend = array_filter(
            $this->logTexte(),
            fn (string $text): bool => str_contains($text, self::MELDUNG)
                && $this->nenntAnfrage($text, $anfrage->id, $anfrage->nummer)
        );
        $this->assertNotEmpty($passend, "Kein Log-Eintrag mit Meldung und id/nummer der Anfrage. Einträge:\n".implode("\n", $this->logTexte()));
    }

    // ---------------------------------------------------------------- AK4

    // AK4 (AK1)
    #[DataProvider('andereAusnahmen')]
    public function test_andere_ausnahme_leitet_auf_danke_formular_weiter(Throwable $ausnahme): void
    {
        $this->mailerWirft($ausnahme);

        $this->post(route('anfrage'), $this->gueltigeAnfrage())
            ->assertRedirect(route('danke_formular'));
    }

    // AK4 (AK2)
    #[DataProvider('andereAusnahmen')]
    public function test_andere_ausnahme_laesst_anfrage_und_anhang_genau_einmal_gespeichert(Throwable $ausnahme): void
    {
        $this->mailerWirft($ausnahme);

        $this->post(route('anfrage'), array_merge($this->gueltigeAnfrage(), [
            'Datei' => UploadedFile::fake()->createWithContent('Bericht.pdf', str_repeat('a', 2048)),
        ]))->assertRedirect(route('danke_formular'));

        $anfrage = Kontaktformular::query()->firstOrFail();
        $this->assertDatabaseCount('kontaktformular', 1);
        $this->assertSame('max@example.com', $anfrage->email);
        $this->assertSame(
            ['kontaktformular/'.$anfrage->file_hash.'.zip'],
            Storage::disk('local')->allFiles()
        );
    }

    // AK4 (AK3)
    #[DataProvider('andereAusnahmen')]
    public function test_andere_ausnahme_wird_mit_meldung_und_id_oder_nummer_gemeldet(Throwable $ausnahme): void
    {
        $this->vorhandeneAnfragenAnlegen();
        $this->mailerWirft($ausnahme);

        $this->post(route('anfrage'), $this->gueltigeAnfrage())->assertRedirect(route('danke_formular'));

        $anfrage = Kontaktformular::query()->where('name', 'Max Mustermann')->firstOrFail();
        $passend = array_filter(
            $this->logTexte(),
            fn (string $text): bool => str_contains($text, $ausnahme->getMessage())
                && $this->nenntAnfrage($text, $anfrage->id, $anfrage->nummer)
        );
        $this->assertNotEmpty($passend, "Kein Log-Eintrag mit Meldung und id/nummer. Einträge:\n".implode("\n", $this->logTexte()));
    }

    // AK4 (echte ungültige mailBcc-Adresse)
    public function test_ungueltige_bcc_adresse_haelt_den_besucher_nicht_auf_und_anfrage_bleibt_gespeichert(): void
    {
        config()->set('kontaktformular.mailBcc', 'das ist keine adresse');
        config()->set('mail.default', 'array');

        $this->post(route('anfrage'), $this->gueltigeAnfrage())
            ->assertRedirect(route('danke_formular'));

        $this->assertDatabaseCount('kontaktformular', 1);
        $anfrage = Kontaktformular::query()->firstOrFail();
        $this->assertNotEmpty(
            array_filter($this->logTexte(), fn (string $text): bool => $this->nenntAnfrage($text, $anfrage->id, $anfrage->nummer)),
            'Der Fehler wurde nicht mit id/nummer der Anfrage gemeldet.'
        );
    }

    // ---------------------------------------------------------------- AK5

    // AK5
    public function test_ohne_fehler_wird_die_mail_genau_einmal_an_mail_to_mit_bcc_versendet(): void
    {
        config()->set('kontaktformular.mailBcc', 'lead@example.com');
        Mail::fake();

        $this->post(route('anfrage'), $this->gueltigeAnfrage())
            ->assertRedirect(route('danke_formular'));

        $gesendet = Mail::sent(Anfrage::class);
        $this->assertCount(1, $gesendet);
        $mail = $gesendet->first();
        $mail->render();
        $this->assertTrue($mail->hasTo('to@example.com'));
        $this->assertTrue($mail->hasBcc('lead@example.com'));
        $this->assertDatabaseCount('kontaktformular', 1);
    }

    // AK5
    public function test_ohne_fehler_wird_nichts_als_fehler_gemeldet(): void
    {
        Mail::fake();

        $this->post(route('anfrage'), $this->gueltigeAnfrage())
            ->assertRedirect(route('danke_formular'));

        // Gegenprobe: Der Versand lief wirklich
        $this->assertCount(1, Mail::sent(Anfrage::class));

        $fehler = array_filter(
            $this->logHandler()->getRecords(),
            static fn ($eintrag): bool => $eintrag->level->value >= 400
        );
        $this->assertSame([], array_values($fehler), 'Es wurde ein Fehler gemeldet, obwohl der Versand klappte.');
        $this->assertFalse($this->logHandler()->hasErrorRecords());
    }

    // ---------------------------------------------------------------- Randfälle

    // Randfall 1
    public function test_validierungsfehler_bleibt_unveraendert(): void
    {
        $this->mailerWirft(new TransportException(self::MELDUNG));

        // Gegenprobe: gültig → Danke-Seite
        $this->post(route('anfrage'), $this->gueltigeAnfrage())->assertRedirect(route('danke_formular'));
        $this->assertDatabaseCount('kontaktformular', 1);

        $ungueltig = ['Email' => 'keine-mail'] + $this->gueltigeAnfrage();
        $this->from('/formular')->post(route('anfrage'), $ungueltig)
            ->assertRedirect('/formular')
            ->assertSessionHasErrors('Email');
        $this->assertDatabaseCount('kontaktformular', 1);
    }

    // Randfall 1
    public function test_bot_erkennung_bleibt_unveraendert(): void
    {
        $this->mailerWirft(new TransportException(self::MELDUNG));

        // Gegenprobe: gültig → Danke-Seite, einmal gespeichert
        $this->post(route('anfrage'), $this->gueltigeAnfrage())->assertRedirect(route('danke_formular'));
        $this->assertDatabaseCount('kontaktformular', 1);

        $this->post(route('anfrage'), array_merge($this->gueltigeAnfrage(), ['fax' => 'gefuellt']))
            ->assertRedirect(route('danke_bot_formular'));
        $this->assertDatabaseCount('kontaktformular', 1);
    }

    // Randfall 2
    public function test_fehler_beim_speichern_wird_nicht_abgefangen(): void
    {
        Mail::fake();

        // Gegenprobe: mit Tabelle klappt es
        $this->post(route('anfrage'), $this->gueltigeAnfrage())->assertRedirect(route('danke_formular'));
        $this->assertCount(1, Mail::sent(Anfrage::class));

        Schema::drop('kontaktformular');

        $this->post(route('anfrage'), $this->gueltigeAnfrage())->assertStatus(500);
        $this->assertCount(1, Mail::sent(Anfrage::class));
    }
}

/** Transport, der beim Senden die vorgegebene Ausnahme wirft. */
final class WerfenderTransport extends AbstractTransport
{
    public function __construct(private readonly Throwable $ausnahme)
    {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        throw $this->ausnahme;
    }

    public function __toString(): string
    {
        return 'wirft://';
    }
}
