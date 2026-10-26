<?php

declare(strict_types=1);

namespace ITHilbert\Kontaktformular\ValueObjects;

use ITHilbert\Kontaktformular\Enums\HerkunftKanal;

/**
 * Herkunft einer Anfrage (First Touch der Session) und ihre Einordnung in einen Kanal.
 */
final readonly class Herkunft
{
    /** Reihenfolge bestimmt, welche Klick-ID bei mehreren genannt wird. */
    public const array KLICK_IDS = ['gclid', 'gbraid', 'wbraid'];

    public const array PARAMETER = [...self::KLICK_IDS, 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term'];

    public const int MAX_LAENGE = 255;

    /**
     * @param  array<string, string>  $parameter
     */
    private function __construct(
        public array $parameter,
        public ?string $einstieg,
        public ?string $referrer,
    ) {}

    /**
     * Unbekannte Schlüssel, Nicht-Strings und leere Werte werden verworfen, zu lange Werte gekürzt.
     *
     * @param  array<mixed>|null  $daten
     */
    public static function fromArray(?array $daten): self
    {
        $daten ??= [];

        $parameter = [];
        foreach (self::PARAMETER as $name) {
            $wert = self::normalisieren($daten[$name] ?? null);
            if ($wert !== null) {
                $parameter[$name] = $wert;
            }
        }

        return new self(
            $parameter,
            self::normalisieren($daten['einstieg'] ?? null),
            self::normalisieren($daten['referrer'] ?? null),
        );
    }

    /**
     * Nur die erfassten Werte, ohne leere Schlüssel.
     *
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return array_filter(
            [...$this->parameter, 'einstieg' => $this->einstieg, 'referrer' => $this->referrer],
            static fn (?string $wert): bool => $wert !== null,
        );
    }

    public function kanal(): HerkunftKanal
    {
        if ($this->klickId() !== null || $this->istGoogleCpc()) {
            return HerkunftKanal::GoogleAds;
        }

        if ($this->referrer !== null) {
            return HerkunftKanal::OrganischSonstige;
        }

        return HerkunftKanal::Direkt;
    }

    /**
     * Name (nie Wert) der ersten vorhandenen Klick-ID.
     */
    public function klickId(): ?string
    {
        foreach (self::KLICK_IDS as $name) {
            if (isset($this->parameter[$name])) {
                return $name;
            }
        }

        return null;
    }

    private function istGoogleCpc(): bool
    {
        return mb_strtolower($this->parameter['utm_source'] ?? '') === 'google'
            && mb_strtolower($this->parameter['utm_medium'] ?? '') === 'cpc';
    }

    private static function normalisieren(mixed $wert): ?string
    {
        if (! is_string($wert)) {
            return null;
        }

        $wert = trim(self::ohneSteuerzeichen($wert));

        return $wert === '' ? null : mb_substr($wert, 0, self::MAX_LAENGE);
    }

    /**
     * Entfernt Steuerzeichen (Unicode-Kategorie Cc: U+0000–001F, U+007F–009F) sowie U+2028/U+2029 ersatzlos; bytegenau, daher auch bei ungültigem UTF-8.
     */
    public static function ohneSteuerzeichen(string $wert): string
    {
        return preg_replace('/[\x00-\x1F\x7F]|\xC2[\x80-\x9F]|\xE2\x80[\xA8\xA9]/', '', $wert) ?? '';
    }
}
