<?php

namespace JoeDixon\Translation;

use Illuminate\Support\ServiceProvider;
use JoeDixon\TranslationCore\Providers\Eloquent\Translation;
use JoeDixon\TranslationCore\Configuration;
use JoeDixon\TranslationCore\TranslationProvider;

class TranslationServiceProvider extends ServiceProvider
{
    /**
     * Register package bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfiguration();
    }

    /**
     * Bootstrap the package services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViews();
        $this->loadTranslations();
        $this->registerRoutes();
        $this->publishAssets();
        $this->registerHelpers();
        $this->publishConfiguration();
        $this->registerTranslationProvider();
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
     * Load package translations.
     *
     * @return void
     */
    private function loadTranslations()
    {
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'translation');

        $this->publishes([
            __DIR__.'/../resources/lang' => lang_path('vendor/translation'),
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
     * Register package helper functions.
     *
     * @return void
     */
    private function registerHelpers()
    {
        require __DIR__.'/../resources/helpers.php';
    }

    protected function registerTranslationProvider()
    {
        $config = $this->app['config']['translation'];
        $configuration = new Configuration(
            $config['driver'],
            $config['translation_methods'],
            $config['scan_paths'],
            $config['database']
        );

        TranslationProvider::init(
            $this->app, 
            $configuration
        );
    }
}
