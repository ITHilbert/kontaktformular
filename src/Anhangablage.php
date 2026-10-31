<?php

declare(strict_types=1);

namespace ITHilbert\Kontaktformular;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/**
 * Einzige Stelle für Pfade und Zugriffe auf Anhänge: alles läuft über die Disk `kontaktformular.disk`.
 */
final class Anhangablage
{
    private const VERZEICHNIS = 'kontaktformular';

    public function disk(): FilesystemAdapter
    {
        /** @var FilesystemAdapter */
        return Storage::disk((string) config('kontaktformular.disk', 'local'));
    }

    public function zipPfad(string $hash): string
    {
        return self::VERZEICHNIS.'/'.$hash.'.zip';
    }

    /**
     * Speichert den Upload auf der Disk und packt ihn (außer ZIP) zu `kontaktformular/<hash>.zip`.
     *
     * @return string der Hash (Dateiname ohne Endung)
     */
    public function speichern(UploadedFile $upload, string $dateiname, string $dateityp): string
    {
        $disk = $this->disk();
        $gespeichert = $disk->putFile(self::VERZEICHNIS, $upload);
        $hash = pathinfo((string) $gespeichert, PATHINFO_FILENAME);

        if ($dateityp !== 'zip') {
            $zip = new ZipArchive;
            $zip->open($disk->path($this->zipPfad($hash)), ZipArchive::CREATE | ZipArchive::OVERWRITE);
            $zip->addFile($disk->path((string) $gespeichert), $dateiname);
            $zip->close();

            $disk->delete((string) $gespeichert);
        }

        return $hash;
    }

    public function existiert(string $hash): bool
    {
        return $this->disk()->exists($this->zipPfad($hash));
    }

    public function lokalerPfad(string $hash): string
    {
        return $this->disk()->path($this->zipPfad($hash));
    }

    public function loeschen(string $hash): void
    {
        $this->disk()->delete($this->zipPfad($hash));
    }
}
