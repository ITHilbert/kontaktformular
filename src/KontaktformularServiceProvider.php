<?php

declare(strict_types=1);

namespace ITHilbert\Kontaktformular;

use Illuminate\Support\ServiceProvider;

class KontaktformularServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/Resources/views', 'kontaktformular');
        $this->loadTranslationsFrom(__DIR__ . '/Resources/lang', 'kontaktformular');
        $this->loadRoutesFrom(__DIR__ . '/Routes/web.php');
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');

        $this->mergeConfigFrom(__DIR__ . '/Config/config.php', 'kontaktformular');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\KontaktformularCommand::class,
            ]);

            $this->publishes([
                __DIR__ . '/Config/config.php' => config_path('kontaktformular.php'),
            ], 'kontaktformular-config');

            $this->publishes([
                __DIR__ . '/Resources/assets' => public_path('vendor/kontaktformular'),
            ], 'kontaktformular-assets');

            $this->publishes([
                __DIR__ . '/Resources/views' => resource_path('views/vendor/kontaktformular'),
            ], 'kontaktformular-views');
        }
    }

    #[\Override]
    public function register(): void
    {
    }
}
