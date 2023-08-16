<?php

namespace ITHilbert\Kontaktformular;

use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;
use ITHilbert\Kontaktformular\Commands\KontaktformularCommand;

class KontaktformularServiceProvider extends ServiceProvider
{

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {

        $this->registerViews();
        $this->publishAssets();
        $this->registerConfig();
        $this->registerTranslations();
        $this->registerRoutes();
        $this->registerCommands();
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');

    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $this->loadViewsFrom(__DIR__ .'/Resources/views', 'kontaktformular');
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $this->loadTranslationsFrom(__DIR__ .'/Resources/lang', 'kontaktformular');
    }



    /**
     * Register commands.
     *
     * @return void
     */
    protected function registerCommands()
    {
         $this->commands( KontaktformularCommand::class );
    }

    /**
     * Middlewares
     *
     * @return void
     */
    public function registerMiddleware(){
        /* $this->app['router']->aliasMiddleware('MiddlewareName' , \ITHilbert\SITE\Http\Middleware\MiddlewareName::class); */
    }


    /**
     * Assets kopieren
     *
     * @return void
     */
    public function publishAssets()
    {
        $this->publishes([
            __DIR__ .'/Resources/assets' => public_path('vendor/kontaktformular'),
        ]);
    }


    /**
     * Register Routes.
     *
     * @return void
     */
    protected function registerRoutes()
    {
        $this->loadRoutesFrom(__DIR__ . '/Routes/web.php');
    }


    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
       /*  $this->app->register(RouteServiceProvider::class); */
       /* $this->registerBladeExtensions(); */
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            __DIR__ .'/Config/config.php' => config_path('kontaktformular.php')
        ]);
    }





    /**
     * Register an additional directory of factories.
     *
     * @return void
     */
    public function registerFactories()
    {
        /* if (! app()->environment('production') && $this->app->runningInConsole()) {
            app(Factory::class)->load(module_path($this->moduleName, 'Database/factories'));
        } */
    }


    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }


    /**
     * Eigende Blade function (Directive)
     *
     * @return void
     */
    protected function registerBladeExtensions()
    {
        /* $this->app->afterResolving('blade.compiler', function (BladeCompiler $bladeCompiler) {

            $bladeCompiler->directive('hasRole', function ($arguments) {
                list($role, $guard) = explode(',', $arguments.',');

                return "<?php if(auth({$guard})->check() && auth({$guard})->user()->hasRole({$role})): ?>";
            });
            $bladeCompiler->directive('endhasRole', function () {
                return '<?php endif; ?>';
            });

        }); */
    }
}
