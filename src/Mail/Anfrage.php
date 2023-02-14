<?php

namespace ITHilbert\Kontaktformular\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Http\Request;

class Anfrage extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $email;
    public $telefon;
    public $mitteilung;
    public $datenverarbeitung;
    private $file;
    public $fileName;
    public $fromSite;


    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->name = $request->Name;
        $this->email = $request->Email;
        $this->telefon = $request->Telefon;
        $this->mitteilung = $request->Nachricht;
        $this->datenverarbeitung = $request->Datenverarbeitung;
        $this->fromSite = url()->previous();

        if(isset($request->Datei)){
            $this->file = $request->Datei;
            $this->fileName = $request->Datei->getClientOriginalName();
        }
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        if(isset($this->file)){
            return $this->from(config('kontaktformular.mailFrom'))
                        ->subject(config('kontaktformular.subject'))
                        ->replyTo($this->email)
                        ->view('kontaktformular::mail.anfrage')
                        ->attach($this->file, [
                            'as' => $this->fileName,
                        ]);
        }else{
            return $this->from(config('kontaktformular.mailFrom'))
                        ->subject(config('kontaktformular.subject'))
                        ->replyTo($this->email)
                        ->view('kontaktformular::mail.anfrage');
        }
        //->subject('Welcome!');
    }
}
