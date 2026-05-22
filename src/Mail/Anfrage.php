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
        $this->title   = config('kontaktformular.subject') . ' [' . $kontakt->nummer . ']';
    }

    public function build(): self
    {
        return $this->from(config('kontaktformular.mailFrom'))
            ->subject($this->title)
            ->replyTo($this->kontakt->email)
            ->view('kontaktformular::mail.anfrage')
            ->text('kontaktformular::mail.anfrage_plain');
    }
}
