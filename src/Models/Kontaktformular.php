<?php

declare(strict_types=1);

namespace ITHilbert\Kontaktformular\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kontaktformular extends Model
{
    use HasFactory;

    protected $table = 'kontaktformular';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'jahr',
        'nummer',
        'url',
        'name',
        'email',
        'telefon',
        'nachricht',
        'datenverarbeitung',
        'file_name',
        'file_type',
        'file_hash',
        'file_size',
        'file_downloaded_at',
        'file_deleted_at',
    ];

    protected $casts = [
        'jahr'               => 'integer',
        'nummer'             => 'integer',
        'datenverarbeitung'  => 'boolean',
        'file_downloaded_at' => 'datetime',
        'file_deleted_at'    => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if (! isset($this->attributes['jahr'])) {
            $this->jahr = (int) date('Y');
        }
        if (! isset($this->attributes['nummer'])) {
            $this->nummer = $this->getNewNummer();
        }
    }

    private function getNewNummer(): int
    {
        $jahr       = (int) date('Y');
        $maxNummer  = (int) (self::where('jahr', $jahr)->max('nummer') ?? 0);

        return $maxNummer + 1;
    }

    public function getFileUrl(): string
    {
        $url = (string) config('kontaktformular.fileDownloadUrl');
        if (! str_ends_with($url, '/')) {
            $url .= '/';
        }

        return $url . $this->file_hash . '/' . $this->file_name . '/' . $this->id;
    }

    public function getHashWithTimestamp(): string
    {
        return $this->file_hash . '_' . $this->created_at->timestamp;
    }
}
