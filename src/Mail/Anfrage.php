<?php

namespace ITHilbert\Kontaktformular\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use ITHilbert\Kontaktformular\Models\Kontaktformular;

class Anfrage extends Mailable
{
    use Queueable, SerializesModels;

    public Kontaktformular $kontakt;
    public string $fileUrl;
    public string $title;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Kontaktformular $kontakt)
    {
        $this->kontakt = $kontakt;
        $this->fileUrl = $kontakt->getFileUrl();
        $this->title = config('kontaktformular.subject') .' ['. $kontakt->nummer .']';
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from(config('kontaktformular.mailFrom'))
                    ->subject(config('kontaktformular.subject') .' ['. $this->kontakt->nummer .']')
                    ->replyTo($this->kontakt->email)
                    ->view('kontaktformular::mail.anfrage')
                    ->text('kontaktformular::mail.anfrage_plain');
    }
}
