<?php

namespace ITHilbert\Kontaktformular\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kontaktformular extends Model
{
    use HasFactory;

    protected $table = 'Kontaktformular';
    protected $primaryKey = 'id';
    protected $guarded = ['id'];
    public $timestamps = true;
}
