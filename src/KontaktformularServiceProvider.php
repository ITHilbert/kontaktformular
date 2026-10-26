<?php

declare(strict_types=1);

namespace ITHilbert\Kontaktformular;

use Illuminate\Contracts\Http\Kernel as HttpKernelContract;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Routing\Router;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\ServiceProvider;
use ITHilbert\Kontaktformular\Middleware\HerkunftErfassen;

class KontaktformularServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/Resources/views', 'kontaktformular');
        $this->loadTranslationsFrom(__DIR__.'/Resources/lang', 'kontaktformular');
        $this->loadRoutesFrom(__DIR__.'/Routes/web.php');
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

        $this->mergeConfigFrom(__DIR__.'/Config/config.php', 'kontaktformular');

        $this->registriereHerkunftMiddleware();

        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\KontaktformularCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/Config/config.php' => config_path('kontaktformular.php'),
            ], 'kontaktformular-config');

            $this->publishes([
                __DIR__.'/Resources/assets' => public_path('vendor/kontaktformular'),
            ], 'kontaktformular-assets');

            $this->publishes([
                __DIR__.'/Resources/views' => resource_path('views/vendor/kontaktformular'),
            ], 'kontaktformular-views');
        }
    }

    /**
     * Reiht die Middleware in der Gruppe `web` direkt nach `StartSession` ein, auch in der Priorität.
     * Sonst sortiert Laravel `auth`, `throttle` und die CSRF-Prüfung davor, und ein Abbruch dort
     * (302, 429, 419) verhindert das Erfassen.
     *
     * Eingehängt wird am Router. Entsteht der HTTP-Kernel erst nach dem Boot (z. B. Testbench),
     * überschreibt sein Konstruktor Gruppen und Priorität des Routers – nur dann wird beides
     * zusätzlich im Kernel ergänzt (`afterResolving`, nie `callAfterResolving`): Ein bereits
     * aufgelöster Kernel (public/index.php) würde seine Gruppen erneut auf den Router schreiben
     * und dort von anderen Paketen eingehängte Middlewares (z. B. 2FA von `userauth`) verwerfen.
     */
    private function registriereHerkunftMiddleware(): void
    {
        $router = $this->app->make(Router::class);
        $router->middlewareGroup('web', self::gruppeMitHerkunft($router->getMiddlewareGroups()['web'] ?? []));
        $router->middlewarePriority = self::prioritaetMitHerkunft($router->middlewarePriority);

        $this->app->afterResolving(HttpKernelContract::class, static function (HttpKernelContract $kernel): void {
            if (! $kernel instanceof HttpKernel) {
                return;
            }

            $gruppen = $kernel->getMiddlewareGroups();
            if (array_key_exists('web', $gruppen)) {
                $gruppen['web'] = self::gruppeMitHerkunft($gruppen['web']);
                $kernel->setMiddlewareGroups($gruppen);
            }

            $kernel->setMiddlewarePriority(self::prioritaetMitHerkunft($kernel->getMiddlewarePriority()));
        });
    }

    /**
     * Fehlt `StartSession` in der Gruppe, wird wie bisher angehängt: Startet die App die Session
     * anderswo (z. B. global), wird so weiter erfasst; ohne Session tut die Middleware nichts.
     *
     * @param  array<int|string, mixed>  $gruppe
     * @return list<mixed>
     */
    private static function gruppeMitHerkunft(array $gruppe): array
    {
        $index = self::indexVon(array_values($gruppe), StartSession::class);
        if ($index === null) {
            return in_array(HerkunftErfassen::class, $gruppe, true)
                ? array_values($gruppe)
                : [...array_values($gruppe), HerkunftErfassen::class];
        }

        return self::nachEinreihen(array_values($gruppe), $index);
    }

    /**
     * Fehlt `StartSession` in der Priorität, bleibt sie unverändert: Ohne Anker gäbe es keine
     * sinnvolle Stelle, und die Position in der Gruppe bleibt dann ohnehin erhalten.
     *
     * @param  array<int|string, mixed>  $prioritaet
     * @return list<mixed>
     */
    private static function prioritaetMitHerkunft(array $prioritaet): array
    {
        $index = self::indexVon(array_values($prioritaet), StartSession::class);

        return $index === null ? array_values($prioritaet) : self::nachEinreihen(array_values($prioritaet), $index);
    }

    /**
     * @param  list<mixed>  $liste
     * @param  class-string  $klasse
     */
    private static function indexVon(array $liste, string $klasse): ?int
    {
        foreach ($liste as $index => $eintrag) {
            if (is_string($eintrag) && is_a($eintrag, $klasse, true)) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Setzt die Middleware direkt hinter den Eintrag `$index`; ein vorhandener Eintrag wird verschoben.
     *
     * @param  list<mixed>  $liste
     * @return list<mixed>
     */
    private static function nachEinreihen(array $liste, int $index): array
    {
        $anker = $liste[$index];
        $ohne = array_values(array_filter($liste, static fn (mixed $eintrag): bool => $eintrag !== HerkunftErfassen::class));
        $position = (int) array_search($anker, $ohne, true) + 1;

        array_splice($ohne, $position, 0, [HerkunftErfassen::class]);

        return $ohne;
    }

    #[\Override]
    public function register(): void {}
}
