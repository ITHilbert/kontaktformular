<?php

declare(strict_types=1);

namespace ITHilbert\Kontaktformular\Tests;

use Illuminate\Foundation\Application;
use ITHilbert\Kontaktformular\KontaktformularServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

/**
 * Basis aller Paket-Tests: eigene Testbench-App, unabhängig vom Host.
 */
abstract class TestCase extends Orchestra
{
    /**
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            KontaktformularServiceProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        $app['config']->set('mail.default', 'array');
        $app['config']->set('session.driver', 'array');
        $app['config']->set('cache.default', 'array');
        $app['config']->set('queue.default', 'sync');
    }
}
