<?php

namespace JoeDixon\Translation;

use Illuminate\Support\ServiceProvider;
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
     */
    protected function publishConfiguration(): void
    {
        $this->publishes([
            __DIR__.'/../config/translation.php' => config_path('translation.php'),
        ], 'config');
    }

    /**
     * Merge package configuration.
     */
    protected function mergeConfiguration(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/translation.php', 'translation');
    }

    /**
     * Load and publish package views.
     */
    protected function loadViews(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'translation');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/translation'),
        ]);
    }

    /**
     * Load package translations.
     */
    protected function loadTranslations(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'translation');

        $this->publishes([
            __DIR__.'/../resources/lang' => lang_path('vendor/translation'),
        ]);
    }

    /**
     * Register package routes.
     */
    protected function registerRoutes(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }

    /**
     * Publish package assets.
     */
    protected function publishAssets(): void
    {
        $this->publishes([
            __DIR__.'/../public/assets' => public_path('vendor/translation'),
        ], 'assets');
    }

    /**
     * Register package helper functions.
     */
    protected function registerHelpers(): void
    {
        require __DIR__.'/../resources/helpers.php';
    }

    /**
     * Register the translation core provider.
     */
    protected function registerTranslationProvider(): void
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
