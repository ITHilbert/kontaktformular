<?php

declare(strict_types=1);

namespace ITHilbert\Kontaktformular\Exceptions;

use ITHilbert\Kontaktformular\Models\Kontaktformular;
use RuntimeException;
use Throwable;

final class AnfrageMailNichtVersendet extends RuntimeException
{
    private function __construct(string $message, private readonly Kontaktformular $anfrage, Throwable $previous)
    {
        parent::__construct($message, 0, $previous);
    }

    public static function fuer(Kontaktformular $anfrage, Throwable $ursache): self
    {
        return new self(
            'Anfrage-Mail nicht versendet: '.$ursache->getMessage(),
            $anfrage,
            $ursache,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return [
            'id' => $this->anfrage->id,
            'nummer' => $this->anfrage->nummer,
        ];
    }
}
