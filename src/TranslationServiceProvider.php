<?php

namespace JoeDixon\Translation;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\Translator;
use Illuminate\Support\ServiceProvider;
use JoeDixon\Translation\Drivers\DriverInterface;
use JoeDixon\Translation\Drivers\File;
use JoeDixon\Translation\Console\Commands\AddLanguageCommand;
use JoeDixon\Translation\Console\Commands\ListLanguagesCommand;
use JoeDixon\Translation\Console\Commands\AddTranslationKeyCommand;
use JoeDixon\Translation\Console\Commands\ListMissingTranslationKeys;
use JoeDixon\Translation\Console\Commands\SynchroniseTranslationsCommand;
use JoeDixon\Translation\Console\Commands\SynchroniseMissingTranslationKeys;

class TranslationServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the package services.
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

        $this->registerHelpers();
    }

    /**
     * Register package bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfiguration();

        $this->registerContainerBindings();

        $this->registerLoader();

        $this->registerTranslator();
    }

    /**
     * Load and publish package views.
     *
     * @return void
     */
    private function loadViews()
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'translation');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/translation'),
        ]);
    }

    /**
     * Register package routes.
     *
     * @return void
     */
    private function registerRoutes()
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }

    /**
     * Publish package configuration.
     *
     * @return void
     */
    private function publishConfiguration()
    {
        $this->publishes([
            __DIR__.'/../config/translation.php' => config_path('translation.php'),
        ], 'config');
    }

    /**
     * Merge package configuration.
     *
     * @return void
     */
    private function mergeConfiguration()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/translation.php', 'translation');
    }

    /**
     * Publish package assets.
     *
     * @return void
     */
    private function publishAssets()
    {
        $this->publishes([
            __DIR__.'/../public/assets' => public_path('vendor/translation'),
        ], 'assets');
    }

    /**
     * Load package migrations.
     *
     * @return void
     */
    private function loadMigrations()
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    /**
     * Load package translations.
     *
     * @return void
     */
    private function loadTranslations()
    {
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'translation');

        $this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/translation'),
        ]);
    }

    /**
     * Register package commands.
     *
     * @return void
     */
    private function registerCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                AddLanguageCommand::class,
                AddTranslationKeyCommand::class,
                ListLanguagesCommand::class,
                ListMissingTranslationKeys::class,
                SynchroniseMissingTranslationKeys::class,
                SynchroniseTranslationsCommand::class,
            ]);
        }
    }

    /**
     * Register package bindings in the container.
     *
     * @return void
     */
    protected function registerContainerBindings()
    {
        $this->app->singleton(Scanner::class, function () {
            $config = $this->app['config']['translation'];

            return new Scanner(new Filesystem, $config['scan_paths'], $config['translation_methods']);
        });

        $this->app->singleton(DriverInterface::class, function ($app) {
            return (new TranslationManager($app, $app['config']['translation'], $app->make(Scanner::class)))->resolve();
        });
    }

    /**
     * Register package helper functions.
     *
     * @return void
     */
    private function registerHelpers()
    {
        require __DIR__.'/../resources/helpers.php';
    }

    /**
     * Register the translation line loader.
     *
     * @return void
     */
    protected function registerLoader()
    {
        $this->app->singleton('translation.loader', function ($app) {
            if ($this->app['config']['translation.driver'] == 'database') {
                return new ContractDatabaseLoader($this->app->make(DriverInterface::class));
            } else {
                return new File($app['files'], $app['path.lang']);
            }
        });
    }

    /**
     * Register the translator component.
     *
     * @return void
     */
    protected function registerTranslator()
    {
        $this->app->singleton('translator', function ($app) {
            $loader = $app['translation.loader'];

            // When registering the translator component, we'll need to set the default
            // locale as well as the fallback locale. So, we'll grab the application
            // configuration so we can easily get both of these values from there.
            $locale = $app['config']['app.locale'];

            $trans = new Translator($loader, $locale);

            $trans->setFallback($app['config']['app.fallback_locale']);

            return $trans;
        });
    }
}
