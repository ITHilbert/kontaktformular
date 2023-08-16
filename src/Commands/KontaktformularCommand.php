<?php

namespace ITHilbert\Kontaktformular\Commands;

use Illuminate\Console\Command;
use ITHilbert\Kontaktformular\Models\Kontaktformular;


class KontaktformularCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kontaktformular:delete_old_files';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Löscht die Dateien die älter als 30 Tage sind vom Kontaktformular.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //Datum von vor 30 Tagen
        $date = date('Y-m-d', strtotime('-30 days'));

        //Alle Dateien die älter als 30 Tage sind
        $kontakte = Kontaktformular::where('created_at', '<', $date)->whereNull('file_deleted_at')->get();

        //Alle Dateien löschen
        foreach ($kontakte as $kontakt) {
            $file = storage_path('app/kontaktformular/' . $kontakt->file_hash . '.zip');
            //prüfen ob file existiert
            if (file_exists($file)) {
                unlink($file);
            }
            $kontakt->file_deleted_at = date('Y-m-d H:i:s');
            $kontakt->update();
        }

        //Ausgabe
        $this->info('Die Dateien die älter als 30 Tage sind wurden gelöscht.');
    }
}
