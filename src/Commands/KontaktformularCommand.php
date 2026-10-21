<?php

declare(strict_types=1);

namespace ITHilbert\Kontaktformular\Commands;

use Illuminate\Console\Command;
use ITHilbert\Kontaktformular\Models\Kontaktformular;

final class KontaktformularCommand extends Command
{
    protected $signature = 'kontaktformular:delete_old_files';

    protected $description = 'Löscht Anhänge und optional Anfragen des Kontaktformulars nach den konfigurierten Fristen.';

    public function handle(): int
    {
        $dateiFrist = $this->tage(config('kontaktformular.dateien_loeschen_nach_tagen'), false);
        $anfrageFrist = $this->tage(config('kontaktformular.anfragen_loeschen_nach_tagen'), true);

        if ($dateiFrist === false || $anfrageFrist === false) {
            $this->error('Ungültige Löschfrist: erlaubt sind positive ganze Zahlen (Anfragenfrist zusätzlich leer). Es wurde nichts gelöscht.');

            return self::FAILURE;
        }

        $anhaenge = 0;
        $anfragen = 0;

        if ($anfrageFrist !== null) {
            $alte = Kontaktformular::where('created_at', '<', now()->subDays($anfrageFrist))->get();

            foreach ($alte as $anfrage) {
                if ($this->dateiLoeschen($anfrage)) {
                    $anhaenge++;
                }
                $anfrage->delete();
                $anfragen++;
            }
        }

        $zuLoeschen = Kontaktformular::where('created_at', '<', now()->subDays((int) $dateiFrist))
            ->whereNull('file_deleted_at')
            ->whereNotNull('file_hash')
            ->get();

        foreach ($zuLoeschen as $anfrage) {
            $this->dateiLoeschen($anfrage);
            $anfrage->file_deleted_at = now();
            $anfrage->save();
            $anhaenge++;
        }

        $this->info("Gelöschte Anhänge: {$anhaenge}");
        $this->info("Gelöschte Anfragen: {$anfragen}");

        return self::SUCCESS;
    }

    /**
     * @return int|false|null int = gültig, null = nicht gesetzt (nur wenn erlaubt), false = ungültig
     */
    private function tage(mixed $wert, bool $nullErlaubt): int|false|null
    {
        if (($wert === null || $wert === '') && $nullErlaubt) {
            return null;
        }

        if (is_bool($wert) || ! (is_int($wert) || is_string($wert))) {
            return false;
        }

        $tage = filter_var($wert, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $tage === false ? false : $tage;
    }

    /**
     * Entfernt die Anhang-Datei, falls vorhanden. Gibt zurück, ob ein Anhang betroffen war.
     */
    private function dateiLoeschen(Kontaktformular $anfrage): bool
    {
        if ($anfrage->file_hash === null || $anfrage->file_hash === '') {
            return false;
        }

        $datei = storage_path('app/kontaktformular/' . $anfrage->file_hash . '.zip');
        if (file_exists($datei)) {
            unlink($datei);
        }

        return $anfrage->file_deleted_at === null;
    }
}
