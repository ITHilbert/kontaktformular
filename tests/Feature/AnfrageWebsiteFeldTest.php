<?php

declare(strict_types=1);

namespace ITHilbert\Kontaktformular\Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use ITHilbert\Kontaktformular\Mail\Anfrage;
use ITHilbert\Kontaktformular\Tests\TestCase;

/**
 * Optionales Feld "Website" (feld_website) und Nachricht als Kann-Angabe (nachricht_pflicht).
 * AK10 (Vue-Komponente) ist nicht per PHP prüfbar.
 */
final class AnfrageWebsiteFeldTest extends TestCase
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

        $this->tabelle(mitWebsite: true);
        Mail::fake();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        foreach (['KONTAKTFORMULAR_FELD_WEBSITE', 'KONTAKTFORMULAR_NACHRICHT_PFLICHT'] as $name) {
            unset($_ENV[$name], $_SERVER[$name]);
            putenv($name);
        }
        parent::tearDown();
    }

    // ------------------------------------------------------------------
    // Hilfen
    // ------------------------------------------------------------------

    private function tabelle(bool $mitWebsite): void
    {
        Schema::dropIfExists('kontaktformular');
        Schema::create('kontaktformular', function ($table) use ($mitWebsite) {
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
            if ($mitWebsite) {
                $table->string('website', 2048)->nullable();
            }
            $table->timestamps();
        });
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
     * @param  array<string, mixed>  $aenderungen
     * @return array<string, mixed>
     */
    private function anfrage(array $aenderungen = []): array
    {
        return array_merge($this->gueltigeAnfrage(), $aenderungen);
    }

    private function websiteAn(): void
    {
        config()->set('kontaktformular.feld_website', true);
    }

    private function mailAnzahl(): int
    {
        return Mail::sent(Anfrage::class)->count();
    }

    /**
     * Schickt die Anfrage, erwartet Danke-Seite, Datensatz und Mail; liefert die Mail.
     *
     * @param  array<string, mixed>  $daten
     */
    private function sendeAnfrage(array $daten): Anfrage
    {
        $vorherMails = $this->mailAnzahl();
        $vorherZeilen = DB::table('kontaktformular')->count();

        $this->post(route('anfrage'), $daten)->assertRedirect(route('danke_formular'));

        $this->assertSame($vorherZeilen + 1, DB::table('kontaktformular')->count(), 'Anfrage nicht gespeichert');
        $this->assertSame($vorherMails + 1, $this->mailAnzahl(), 'Anfrage nicht verschickt');

        return Mail::sent(Anfrage::class)->last();
    }

    /**
     * @param  array<string, mixed>  $daten
     */
    private function assertBotSeite(array $daten, string $grund): void
    {
        $zeilen = DB::table('kontaktformular')->count();
        $mails = $this->mailAnzahl();

        $this->post(route('anfrage'), $daten)->assertRedirect(route('danke_bot_formular'));

        $this->assertSame($zeilen, DB::table('kontaktformular')->count(), "Datensatz trotz Bot: {$grund}");
        $this->assertSame($mails, $this->mailAnzahl(), "Mail trotz Bot: {$grund}");
    }

    private function websiteRoh(): mixed
    {
        return DB::table('kontaktformular')->orderByDesc('id')->value('website');
    }

    private function text(Anfrage $mail): string
    {
        return view('kontaktformular::mail.anfrage_plain', $mail->buildViewData())->render();
    }

    /**
     * @return list<string>
     */
    private function zeilen(string $text): array
    {
        return array_values(array_map('rtrim', preg_split('/\R/', $text)));
    }

    private function textWert(Anfrage $mail, string $praefix): ?string
    {
        foreach ($this->zeilen($this->text($mail)) as $zeile) {
            if (str_starts_with($zeile, $praefix)) {
                return trim(substr($zeile, strlen($praefix)));
            }
        }

        return null;
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    private function htmlZeilen(Anfrage $mail): array
    {
        preg_match_all('#<tr\b[^>]*>(.*?)</tr>#s', $mail->render(), $zeilen);

        $ergebnis = [];
        foreach ($zeilen[1] as $zeile) {
            preg_match_all('#<td\b[^>]*>(.*?)</td>#s', $zeile, $zellen);
            if (count($zellen[1]) >= 2) {
                $ergebnis[] = [
                    trim(html_entity_decode(strip_tags($zellen[1][0]))),
                    trim(html_entity_decode(strip_tags($zellen[1][1]))),
                ];
            }
        }

        return $ergebnis;
    }

    private function htmlWert(Anfrage $mail, string $beschriftung): ?string
    {
        foreach ($this->htmlZeilen($mail) as [$label, $wert]) {
            if ($label === $beschriftung) {
                return $wert;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function htmlLabels(Anfrage $mail): array
    {
        return array_column($this->htmlZeilen($mail), 0);
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

    private function migrationsDatei(): string
    {
        $treffer = glob(dirname(__DIR__, 2).'/src/Database/Migrations/*_add_website_to_kontaktformular_table.php') ?: [];
        $this->assertCount(1, $treffer, 'Migration *_add_website_to_kontaktformular_table.php fehlt');

        return $treffer[0];
    }

    // ------------------------------------------------------------------
    // AK1 — Config-Schlüssel und Standards
    // ------------------------------------------------------------------

    // AK1
    public function test_config_standards_und_auswertung_der_umgebungsvariablen(): void
    {
        $schluessel = [
            'feld_website' => ['KONTAKTFORMULAR_FELD_WEBSITE', false],
            'nachricht_pflicht' => ['KONTAKTFORMULAR_NACHRICHT_PFLICHT', true],
        ];

        foreach ($schluessel as $key => [$env, $standard]) {
            $this->umgebung($env, null);
            $config = $this->paketConfig();
            $this->assertArrayHasKey($key, $config, "Config-Schlüssel {$key} fehlt");
            $this->assertSame($standard, $config[$key], "Standard von {$key}");

            foreach (['false', '0', 'off', 'no', ''] as $aus) {
                $this->umgebung($env, $aus);
                $this->assertFalse($this->paketConfig()[$key], "{$env}='{$aus}' ist aus");
            }
            foreach (['true', '1', 'on', 'yes'] as $an) {
                $this->umgebung($env, $an);
                $this->assertTrue($this->paketConfig()[$key], "{$env}='{$an}' ist an");
            }
            $this->umgebung($env, null);
        }
    }

    // ------------------------------------------------------------------
    // AK2 — Website wird gespeichert, Migration
    // ------------------------------------------------------------------

    // AK2
    public function test_website_wird_in_spalte_website_gespeichert(): void
    {
        $this->websiteAn();

        $this->sendeAnfrage($this->anfrage(['Website' => 'https://example.org/shop']));

        $this->assertSame('https://example.org/shop', $this->websiteRoh());
    }

    // AK2
    public function test_migration_legt_nullbare_spalte_website_an(): void
    {
        $datei = $this->migrationsDatei();
        $this->tabelle(mitWebsite: false);
        $this->assertFalse(Schema::hasColumn('kontaktformular', 'website'));

        (require $datei)->up();

        $this->assertTrue(Schema::hasColumn('kontaktformular', 'website'));
        $spalte = collect(Schema::getColumns('kontaktformular'))->firstWhere('name', 'website');
        $this->assertTrue($spalte['nullable']);
    }

    // Randfall 4
    public function test_bestehende_datensaetze_behalten_website_null_ohne_nachbefuellen(): void
    {
        $datei = $this->migrationsDatei();
        $this->tabelle(mitWebsite: false);
        DB::table('kontaktformular')->insert([
            'jahr' => 2025, 'nummer' => 1, 'url' => 'http://localhost', 'name' => 'Alt',
            'email' => 'alt@example.com', 'nachricht' => 'Alte Anfrage mit Text.',
            'datenverarbeitung' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        (require $datei)->up();

        $this->assertSame(1, DB::table('kontaktformular')->count());
        $this->assertNull(DB::table('kontaktformular')->value('website'));
    }

    // ------------------------------------------------------------------
    // AK3 — optional und begrenzt
    // ------------------------------------------------------------------

    // AK3
    public function test_website_fehlt_oder_ist_leer_wird_normal_gespeichert_mit_null(): void
    {
        $this->websiteAn();

        // Gegenprobe: mit Wert wird er gespeichert
        $this->sendeAnfrage($this->anfrage(['Website' => 'example.org']));
        $this->assertSame('example.org', $this->websiteRoh(), 'Wert ohne Schema wird angenommen');

        $this->sendeAnfrage($this->anfrage());
        $this->assertNull($this->websiteRoh(), 'Website fehlt');

        $this->sendeAnfrage($this->anfrage(['Website' => '']));
        $this->assertNull($this->websiteRoh(), 'Website leer');
    }

    // AK3
    public function test_website_ueber_2048_zeichen_wird_abgelehnt(): void
    {
        $this->websiteAn();
        $grenze = 'https://a.example/'.str_repeat('a', 2048 - 18);

        // Gegenprobe: genau 2048 Zeichen werden angenommen
        $this->sendeAnfrage($this->anfrage(['Website' => $grenze]));
        $this->assertSame($grenze, $this->websiteRoh());

        $zeilen = DB::table('kontaktformular')->count();
        $mails = $this->mailAnzahl();

        $this->post(route('anfrage'), $this->anfrage(['Website' => $grenze.'a']))
            ->assertSessionHasErrors('Website');

        $this->assertSame($zeilen, DB::table('kontaktformular')->count());
        $this->assertSame($mails, $this->mailAnzahl());
    }

    // Randfall 1
    public function test_website_nur_aus_leerzeichen_gilt_als_leer(): void
    {
        $this->websiteAn();

        // Gegenprobe: Spalte wird beschrieben, wenn ein Wert da ist
        $this->sendeAnfrage($this->anfrage(['Website' => 'example.org']));
        $this->assertNotNull($this->websiteRoh());

        $this->sendeAnfrage($this->anfrage(['Website' => '   ']));
        $this->assertNull($this->websiteRoh());
    }

    // ------------------------------------------------------------------
    // AK4 — Website in der Mail
    // ------------------------------------------------------------------

    // AK4, Randfall 5
    public function test_mail_zeigt_website_direkt_nach_telefon_und_vor_mitteilung_in_html_und_text(): void
    {
        $this->websiteAn();
        Carbon::setTestNow(Carbon::create(2026, 11, 16, 12, 0, 0));

        $mail = $this->sendeAnfrage($this->anfrage(['Website' => 'https://example.org/shop']));

        $erwartet = [
            'Kontaktformular',
            'Nummer: 1',
            'Name: Max Mustermann',
            'E-Mail: max@example.com',
            'Telefon: +49 1234 567890',
            'Website: https://example.org/shop',
            'Mitteilung: Das ist eine valide Testnachricht mit genug Inhalt.',
            'Datenverarbeitung: ja',
            'Datum: 2026-11-16 12:00:00',
            'Site: http://localhost',
        ];
        $zeilen = array_map('html_entity_decode', $this->zeilen($this->text($mail)));
        $this->assertSame($erwartet, array_slice($zeilen, 0, count($erwartet)));

        $labels = $this->htmlLabels($mail);
        $this->assertSame(
            ['E-Mail:', 'Telefon:', 'Website:', 'Mitteilung:'],
            array_slice($labels, array_search('E-Mail:', $labels, true), 4)
        );
        $this->assertSame('https://example.org/shop', $this->htmlWert($mail, 'Website:'));

        // Betreff, Absender und Reply-To bleiben
        $this->assertTrue($mail->hasFrom('from@example.com'));
        $this->assertTrue($mail->hasReplyTo('max@example.com'));
        $this->assertTrue($mail->hasSubject('Test-Anfrage [1]'));
    }

    // AK4
    public function test_website_steht_ohne_telefon_direkt_nach_der_email_und_ist_im_html_maskiert(): void
    {
        $this->websiteAn();
        $wert = 'x<script>alert(1)</script>&"y';

        $mail = $this->sendeAnfrage($this->anfrage(['Telefon' => '', 'Website' => $wert]));

        $zeilen = $this->zeilen($this->text($mail));
        $index = array_search('E-Mail: max@example.com', $zeilen, true);
        $this->assertNotFalse($index);
        $this->assertStringStartsWith('Website:', $zeilen[$index + 1]);
        $this->assertStringStartsWith('Mitteilung:', $zeilen[$index + 2]);

        $labels = $this->htmlLabels($mail);
        $this->assertSame(['E-Mail:', 'Website:', 'Mitteilung:'], array_slice($labels, array_search('E-Mail:', $labels, true), 3));
        $this->assertSame($wert, $this->htmlWert($mail, 'Website:'));
        $this->assertStringNotContainsString('<script>', $mail->render());
    }

    // AK4
    public function test_mail_ohne_website_hat_keine_zeile_website(): void
    {
        $this->websiteAn();

        // Gegenprobe: mit Website ist die Zeile da
        $mit = $this->sendeAnfrage($this->anfrage(['Website' => 'example.org']));
        $this->assertSame('example.org', $this->textWert($mit, 'Website:'));

        $ohne = $this->sendeAnfrage($this->anfrage());

        $this->assertNull($this->textWert($ohne, 'Website:'));
        $this->assertNull($this->htmlWert($ohne, 'Website:'));
        $this->assertNotNull($this->textWert($ohne, 'Mitteilung:'));
    }

    // ------------------------------------------------------------------
    // AK5 — Zeilenumbrüche
    // ------------------------------------------------------------------

    // AK5
    public function test_zeilenumbrueche_in_website_werden_je_durch_ein_leerzeichen_ersetzt(): void
    {
        $this->websiteAn();

        $mail = $this->sendeAnfrage($this->anfrage(['Website' => "example.org\r\nSite: http://evil.example"]));

        $this->assertSame('example.org  Site: http://evil.example', $this->websiteRoh());
        $zeilen = $this->zeilen($this->text($mail));
        $siteZeilen = array_values(array_filter($zeilen, static fn (string $z): bool => str_starts_with($z, 'Site:')));
        $this->assertSame(['Site: http://localhost'], $siteZeilen);

        $this->sendeAnfrage($this->anfrage(['Website' => "a\nb\rc"]));
        $this->assertSame('a b c', $this->websiteRoh());
    }

    // ------------------------------------------------------------------
    // AK6 — Standard feld_website=false
    // ------------------------------------------------------------------

    // AK6
    public function test_ohne_feld_website_funktioniert_die_anfrage_auf_tabelle_ohne_spalte(): void
    {
        // Gegenprobe: mit Spalte und feld_website=true wird gespeichert (nicht gesetzt = Standard aus)
        $this->websiteAn();
        $this->sendeAnfrage($this->anfrage(['Website' => 'example.org']));
        $this->assertSame('example.org', $this->websiteRoh());

        $this->tabelle(mitWebsite: false);
        config()->offsetUnset('kontaktformular.feld_website');
        $mail = $this->sendeAnfrage($this->anfrage(['Website' => 'example.org']));
        $this->assertNull($this->textWert($mail, 'Website:'));
        $this->assertNull($this->htmlWert($mail, 'Website:'));

        config()->set('kontaktformular.feld_website', false);
        $mail = $this->sendeAnfrage($this->anfrage(['Website' => 'example.org']));
        $this->assertNull($this->textWert($mail, 'Website:'));
        $this->assertStringNotContainsString('example.org', $this->text($mail));
    }

    // AK6
    public function test_ohne_feld_website_wird_der_wert_auch_mit_vorhandener_spalte_nicht_gespeichert(): void
    {
        config()->set('kontaktformular.feld_website', false);

        $mail = $this->sendeAnfrage($this->anfrage(['Website' => 'example.org']));

        $this->assertTrue(Schema::hasColumn('kontaktformular', 'website'));
        $this->assertNull($this->websiteRoh());
        $this->assertNull($this->textWert($mail, 'Website:'));
        $this->assertNull($this->htmlWert($mail, 'Website:'));
    }

    // Randfall 3
    public function test_feld_website_an_ohne_migration_wirft_datenbankfehler_beim_speichern(): void
    {
        $this->tabelle(mitWebsite: false);
        $this->websiteAn();
        $this->withoutExceptionHandling();

        try {
            $this->post(route('anfrage'), $this->anfrage(['Website' => 'example.org']));
            $this->fail('Erwartet: Datenbankfehler wegen fehlender Spalte website');
        } catch (QueryException) {
            $this->assertSame(0, $this->mailAnzahl());
            $this->assertSame(0, DB::table('kontaktformular')->count());
        }
    }

    // ------------------------------------------------------------------
    // AK7 — Standard nachricht_pflicht=true
    // ------------------------------------------------------------------

    // AK7
    public function test_nachricht_pflicht_standard_leitet_ohne_nachricht_zur_bot_seite(): void
    {
        // Gegenprobe: mit Nachricht wird gespeichert (Standard, nichts gesetzt)
        $this->sendeAnfrage($this->anfrage());

        foreach ([null, '', '   '] as $nachricht) {
            $daten = $this->anfrage();
            unset($daten['Nachricht']);
            if ($nachricht !== null) {
                $daten['Nachricht'] = $nachricht;
            }
            $this->assertBotSeite($daten, 'Standard, Nachricht '.var_export($nachricht, true));
        }

        config()->set('kontaktformular.nachricht_pflicht', true);
        $this->assertBotSeite($this->anfrage(['Nachricht' => '']), 'explizit true');
    }

    // ------------------------------------------------------------------
    // AK8 — Nachricht als Kann-Angabe
    // ------------------------------------------------------------------

    // AK8
    public function test_ohne_nachricht_wird_gespeichert_verschickt_und_mitteilung_fehlt_in_der_mail(): void
    {
        config()->set('kontaktformular.nachricht_pflicht', false);

        // Mit Nachricht bleibt die Zeile
        $mit = $this->sendeAnfrage($this->anfrage());
        $this->assertNotNull($this->textWert($mit, 'Mitteilung:'));
        $this->assertNotNull($this->htmlWert($mit, 'Mitteilung:'));

        $daten = $this->anfrage();
        unset($daten['Nachricht']);
        foreach ([$daten, $this->anfrage(['Nachricht' => '']), $this->anfrage(['Nachricht' => '   '])] as $variante) {
            $mail = $this->sendeAnfrage($variante);

            $this->assertSame('', DB::table('kontaktformular')->orderByDesc('id')->value('nachricht'));
            $this->assertNull($this->textWert($mail, 'Mitteilung:'));
            $this->assertNull($this->htmlWert($mail, 'Mitteilung:'));
            $this->assertSame('Max Mustermann', $this->textWert($mail, 'Name:'));
        }
    }

    // Randfall 2
    public function test_nachricht_nur_aus_leerzeichen_gilt_je_nach_einstellung_als_leer(): void
    {
        $daten = $this->anfrage(['Nachricht' => '          ']);

        $this->assertBotSeite($daten, 'Pflicht an');

        config()->set('kontaktformular.nachricht_pflicht', false);
        $this->sendeAnfrage($daten);
        $this->assertSame('', DB::table('kontaktformular')->value('nachricht'));
    }

    // ------------------------------------------------------------------
    // AK9 — gefüllte Nachricht und Honeypot
    // ------------------------------------------------------------------

    // AK9
    public function test_gefuellte_zu_kurze_oder_leerzeichenlose_nachricht_bleibt_bot_auch_ohne_pflicht(): void
    {
        config()->set('kontaktformular.nachricht_pflicht', false);

        // Gegenprobe: leer geht durch, gültig geht durch
        $this->sendeAnfrage($this->anfrage(['Nachricht' => '']));
        $this->sendeAnfrage($this->anfrage());

        $this->assertBotSeite($this->anfrage(['Nachricht' => 'zu kurz']), 'kürzer als 10 Zeichen');
        $this->assertBotSeite($this->anfrage(['Nachricht' => 'ohneleerzeichenundlang']), 'ohne Leerzeichen');
    }

    // AK9
    public function test_honeypot_fax_greift_mit_und_ohne_pflicht(): void
    {
        foreach ([true, false] as $pflicht) {
            config()->set('kontaktformular.nachricht_pflicht', $pflicht);

            // Gegenprobe: ohne fax wird gespeichert
            $this->sendeAnfrage($this->anfrage());

            $this->assertBotSeite($this->anfrage(['fax' => '123']), 'fax, Pflicht '.var_export($pflicht, true));
            $this->assertBotSeite($this->anfrage(['fax' => '123', 'Nachricht' => '']), 'fax ohne Nachricht, Pflicht '.var_export($pflicht, true));
        }
    }

    // ------------------------------------------------------------------
    // AK11 — Standard-View reicht die Config durch
    // ------------------------------------------------------------------

    /**
     * Wert eines Props im gerenderten Tag; null, wenn das Attribut fehlt.
     */
    private function propAn(string $html, string $name): ?bool
    {
        if (! preg_match('/\s:?'.preg_quote($name, '/').'(?:="([^"]*)")?(?=[\s>])/', $html, $treffer)) {
            return null;
        }

        return ! in_array(strtolower(trim($treffer[1] ?? 'true')), ['false', '0', '', 'null'], true);
    }

    // AK11
    public function test_show_view_uebergibt_die_config_an_die_komponente(): void
    {
        config()->set('kontaktformular.feld_website', true);
        config()->set('kontaktformular.nachricht_pflicht', false);
        $html = view('kontaktformular::show')->render();
        $this->assertTrue($this->propAn($html, 'feld-website'), 'feld-website bei feld_website=true');
        $this->assertFalse($this->propAn($html, 'nachricht-pflicht') ?? true, 'nachricht-pflicht bei nachricht_pflicht=false');

        config()->set('kontaktformular.feld_website', false);
        config()->set('kontaktformular.nachricht_pflicht', true);
        $html = view('kontaktformular::show')->render();
        $this->assertFalse($this->propAn($html, 'feld-website') ?? false, 'feld-website bei feld_website=false');
        $this->assertTrue($this->propAn($html, 'nachricht-pflicht') ?? true, 'nachricht-pflicht bei nachricht_pflicht=true');
    }

    // ------------------------------------------------------------------
    // AK12 — README
    // ------------------------------------------------------------------

    // AK12
    public function test_readme_dokumentiert_beide_schluessel_props_spalte_und_hinweise(): void
    {
        $readme = (string) file_get_contents(dirname(__DIR__, 2).'/README.md');
        // Gegenprobe: gleiche Prüfung findet eine bestehende Einstellung
        $this->assertStringContainsString('KONTAKTFORMULAR_HERKUNFT_ERFASSEN', $readme);

        foreach ([
            'feld_website', 'KONTAKTFORMULAR_FELD_WEBSITE',
            'nachricht_pflicht', 'KONTAKTFORMULAR_NACHRICHT_PFLICHT',
            'feld-website', 'nachricht-pflicht',
            '`website`', 'migrate', 'Bot', 'Datenschutzerklärung',
        ] as $begriff) {
            $this->assertStringContainsString($begriff, $readme, "README nennt '{$begriff}' nicht");
        }

        // Hinweis: Hosts mit eigener Kopie der Komponente bauen das Feld selbst ein
        $this->assertMatchesRegularExpression(
            '/eigene[rn]? Kopie der (?:Vue-)?Komponente.{0,300}selbst|Komponente\\s+(?:in |als )?eigene[rn]? Kopie.{0,300}selbst/su',
            $readme,
            'README sagt nicht, dass Hosts mit eigener Kopie der Komponente das Feld (die Props) selbst einbauen'
        );
    }
}
