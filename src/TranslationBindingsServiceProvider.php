<?php

namespace JoeDixon\Translation;

use Illuminate\Translation\TranslationServiceProvider as ServiceProvider;
use Illuminate\Translation\Translator;
use JoeDixon\Translation\Drivers\Translation;

class TranslationBindingsServiceProvider extends ServiceProvider
{
    /**
     * Register package bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app['config']['translation.driver'] === 'database') {
            $this->registerDatabaseTranslator();
        } else {
            parent::register();
        }
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
            $trans = new Translator($loader, $locale);
            $trans->setFallback($app['config']['app.fallback_locale']);

            return $trans;
        });
    }

    protected function registerDatabaseLoader()
    {
        $this->app->singleton('translation.loader', function ($app) {
            // Post Laravel 5.4, the interface was moved to the contracts
            // directory. Here we perform a check to see whether or not the
            // interface exists and instantiate the relevant loader accordingly.
            if (interface_exists('Illuminate\Contracts\Translation\Loader')) {
                return new ContractDatabaseLoader($this->app->make(Translation::class));
            }

            return new InterfaceDatabaseLoader($this->app->make(Translation::class));
        });
    }
}
