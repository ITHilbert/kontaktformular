<?php

declare(strict_types=1);

namespace ITHilbert\Kontaktformular\ValueObjects;

/**
 * Vom Formular übermittelte Adresse der Seite (`site`), aus der die Klick-IDs entfernt werden.
 *
 * Die Adresse wird bewusst nicht zerlegt und neu zusammengesetzt (`parse_url`/`http_build_query`):
 * Schema, Host, Port, Pfad, Fragment sowie Reihenfolge und Kodierung der übrigen Parameter
 * bleiben so Zeichen für Zeichen erhalten.
 */
final readonly class SiteAdresse
{
    private function __construct(private string $wert) {}

    public static function aus(string $wert): self
    {
        return new self(Herkunft::ohneSteuerzeichen($wert));
    }

    /**
     * Entfernt `gclid`, `gbraid` und `wbraid` (ein Paar zählt, wenn PHPs eigener Parser `parse_str` dafür einen dieser Namen mit String-Wert liefert); ohne Klick-ID bleibt der Wert unverändert.
     */
    public function ohneKlickIds(): string
    {
        [$vorFragment, $fragment] = $this->teilen($this->wert, '#');
        [$basis, $query] = $this->teilen($vorFragment, '?');

        if ($query === null) {
            return $this->wert;
        }

        $paare = explode('&', $query);
        $behalten = array_values(array_filter(
            $paare,
            fn (string $paar): bool => ! $this->istKlickId($paar),
        ));

        if (count($behalten) === count($paare)) {
            return $this->wert;
        }

        $neu = $behalten === [] ? $basis : $basis.'?'.implode('&', $behalten);

        return $fragment === null ? $neu : $neu.'#'.$fragment;
    }

    private function istKlickId(string $paar): bool
    {
        parse_str($paar, $gelesen);
        $name = array_key_first($gelesen);

        return $name !== null
            && in_array((string) $name, Herkunft::KLICK_IDS, true)
            && is_string($gelesen[$name]);
    }

    /**
     * Teilt am ersten Vorkommen des Trennzeichens; fehlt es, ist der zweite Teil `null`.
     *
     * @return array{0: string, 1: string|null}
     */
    private function teilen(string $wert, string $trenner): array
    {
        $position = strpos($wert, $trenner);

        return $position === false
            ? [$wert, null]
            : [substr($wert, 0, $position), substr($wert, $position + 1)];
    }
}
