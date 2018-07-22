<?php

namespace JoeDixon\Translation;

use Illuminate\Support\ServiceProvider;

class TranslationServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViews();

        $this->registerRoutes();

        $this->publishConfiguration();

        $this->publishAssets();

        $this->loadMigrations();

        $this->loadTranslations();

        $this->registerCommands();
    }

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfiguration();
    }

    private function loadViews()
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'translation');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/translation'),
        ]);
    }

    private function registerRoutes()
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
    }

    private function publishConfiguration()
    {
        $this->publishes([
            __DIR__ . '/../config/translation.php' => config_path('translation.php'),
        ], 'config');
    }

    private function mergeConfiguration()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/translation.php', 'translation');
    }

    private function publishAssets()
    {
        $this->publishes([
            __DIR__ . '/../resources/assets' => public_path('vendor/translation'),
        ], 'assets');
    }

    private function loadMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    private function loadTranslations()
    {
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'translation');

        $this->publishes([
            __DIR__ . '/../resources/lang' => resource_path('lang/vendor/translation'),
        ]);
    }

    private function registerCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                // Commands will go here
            ]);
        }
    }
}
