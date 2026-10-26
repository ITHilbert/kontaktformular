<?php

declare(strict_types=1);

namespace ITHilbert\Kontaktformular\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Session\CookieSessionHandler;
use ITHilbert\Kontaktformular\ValueObjects\Herkunft;
use Symfony\Component\HttpFoundation\Response;

/**
 * Merkt sich beim ersten Request einer Session die Herkunft (First Touch) serverseitig.
 */
final class HerkunftErfassen
{
    public const string SESSION_KEY = 'kontaktformular_herkunft';

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->darfErfassen($request)) {
            $request->session()->put(self::SESSION_KEY, $this->herkunftAus($request)->toArray());
        }

        return $next($request);
    }

    /**
     * Beim Treiber `cookie` lägen die Session-Daten im Browser – dann wird nichts gespeichert.
     */
    private function darfErfassen(Request $request): bool
    {
        if (! $request->hasSession()) {
            return false;
        }

        $session = $request->session();

        return ! $session->getHandler() instanceof CookieSessionHandler
            && ! $session->has(self::SESSION_KEY);
    }

    private function herkunftAus(Request $request): Herkunft
    {
        $query = $request->query->all();

        $daten = [];
        foreach (Herkunft::PARAMETER as $name) {
            $daten[$name] = $query[$name] ?? null;
        }
        $daten['einstieg'] = $this->einstieg($request);
        $daten['referrer'] = $this->referrerHost($request);

        return Herkunft::fromArray($daten);
    }

    /**
     * Pfad ohne Query, URL-dekodiert (`/über-uns` statt `/%C3%BCber-uns`). Ergibt das Dekodieren
     * kein gültiges UTF-8, bleibt der Pfad kodiert – sonst ließe er sich nicht als JSON speichern.
     */
    private function einstieg(Request $request): string
    {
        $dekodiert = $request->decodedPath();
        $pfad = mb_check_encoding($dekodiert, 'UTF-8') ? $dekodiert : $request->path();

        return '/'.ltrim($pfad, '/');
    }

    /**
     * Nur der Host eines fremden Referrers; eigener Host oder ungültiger Header gelten als kein Referrer.
     */
    private function referrerHost(Request $request): ?string
    {
        $referer = $request->headers->get('referer');
        if (! is_string($referer) || $referer === '') {
            return null;
        }

        $host = parse_url($referer, PHP_URL_HOST);
        if (! is_string($host) || $host === '') {
            return null;
        }

        $host = strtolower($host);
        if (filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
            return null;
        }

        return $host === strtolower($request->getHost()) ? null : $host;
    }
}
