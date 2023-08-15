<?php

namespace ITHilbert\Kontaktformular\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kontaktformular extends Model
{
    use HasFactory;

    protected $table = 'kontaktformular';
    protected $primaryKey = 'id';
    protected $guarded = ['id'];
    public $timestamps = true;

    //den new Kontaktformular überschreiben
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->jahr = date('Y');
        //Nummer setzen, die soll jedes Jahr wieder bei 1 anfangen
        $this->nummer = $this->getNewNummer();
    }

    /**
     * Ermittelt eine neue Nummer für das Kontaktformular
     *
     * @return void
     */
    private function getNewNummer()
    {
        $jahr = date('Y');
        $maxNummer = Kontaktformular::where('jahr', $jahr)->max('nummer');
        if($maxNummer == null){
            $maxNummer = 0;
        }
        return $maxNummer + 1;
    }

    public function getFileUrl(){
        $url = config('kontaktformular.fileDownloadUrl');
        //Prüfen ob $url auf / endet
        if(substr($url, -1) != '/'){
            $url .= '/';
        }
        return $url . $this->filehash .'/' . $this->filename .'/'. $this->id;
    }

}
