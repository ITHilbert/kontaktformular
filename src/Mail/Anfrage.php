<?php

declare(strict_types=1);

namespace ITHilbert\Kontaktformular\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use ITHilbert\Kontaktformular\Models\Kontaktformular;

final class Anfrage extends Mailable
{
    use Queueable, SerializesModels;

    public Kontaktformular $kontakt;

    public string $fileUrl;

    public string $title;

    public function __construct(Kontaktformular $kontakt)
    {
        $this->kontakt = $kontakt;
        $this->fileUrl = $kontakt->getFileUrl();
        $this->title = config('kontaktformular.subject').' ['.$kontakt->nummer.']';

        $bcc = $this->bccAdressen();
        if ($bcc !== []) {
            $this->bcc($bcc);
        }
    }

    public function build(): self
    {
        $this->from(config('kontaktformular.mailFrom'))
            ->subject($this->title)
            ->replyTo($this->kontakt->email)
            ->view('kontaktformular::mail.anfrage')
            ->text('kontaktformular::mail.anfrage_plain');

        return $this;
    }

    /**
     * Normalisiert `kontaktformular.mailBcc` (String mit Komma-Trennung, Array, leer).
     *
     * @return list<string>
     */
    private function bccAdressen(): array
    {
        $wert = config('kontaktformular.mailBcc');
        $teile = is_array($wert) ? $wert : explode(',', is_string($wert) ? $wert : '');

        $adressen = [];
        foreach ($teile as $teil) {
            $adresse = is_string($teil) ? trim($teil) : '';
            if ($adresse !== '') {
                $adressen[] = $adresse;
            }
        }

        return $adressen;
    }
}
