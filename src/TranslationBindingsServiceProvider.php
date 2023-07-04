<?php

namespace JoeDixon\Translation;

use Illuminate\Translation\TranslationServiceProvider as ServiceProvider;
use Illuminate\Translation\Translator;
use JoeDixon\TranslationCore\TranslationManager;

class TranslationBindingsServiceProvider extends ServiceProvider
{
    /**
     * Register package bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        match ($this->app['config']['translation.driver']) {
            'eloquent' => $this->registerDatabaseTranslator(),
            default => parent::register(),
        };
    }

    private function registerDatabaseTranslator()
    {
        $this->registerDatabaseLoader();

        $this->app->singleton('translator', function ($app) {
            $loader = $app['translation.loader'];
            // When registering the translator component, we'll need to set the default
            // locale as well as the fallback locale. So, we'll grab the application
            // configuration so we can easily get both of these values from there.
            $locale = $app['config']['app.locale'];
            $translator = new Translator($loader, $locale);
            $translator->setFallback($app['config']['app.fallback_locale']);

            return $translator;
        });
    }

    protected function registerDatabaseLoader()
    {
        $this->app->singleton('translation.loader', function ($app) {
            return new ContractDatabaseLoader($this->app->make(TranslationManager::class));
        });
    }
}
