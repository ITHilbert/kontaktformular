<?php

declare(strict_types=1);

namespace ITHilbert\Kontaktformular\Commands;

use Illuminate\Console\Command;
use ITHilbert\Kontaktformular\Models\Kontaktformular;

final class KontaktformularCommand extends Command
{
    protected $signature = 'kontaktformular:delete_old_files';

    protected $description = 'Löscht die Dateien, die älter als 30 Tage sind, vom Kontaktformular.';

    public function handle(): int
    {
        $cutoff = now()->subDays(30);

        $kontakte = Kontaktformular::where('created_at', '<', $cutoff)
            ->whereNull('file_deleted_at')
            ->whereNotNull('file_hash')
            ->get();

        foreach ($kontakte as $kontakt) {
            $file = storage_path('app/kontaktformular/' . $kontakt->file_hash . '.zip');
            if (file_exists($file)) {
                unlink($file);
            }
            $kontakt->file_deleted_at = now();
            $kontakt->save();
        }

        $this->info('Die Dateien, die älter als 30 Tage sind, wurden gelöscht.');

        return self::SUCCESS;
    }
}
