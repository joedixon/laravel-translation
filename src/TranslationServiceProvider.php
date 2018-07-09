<?php

namespace JoeDixon\Translation;

use Illuminate\Support\ServiceProvider;

class TranslationServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/config/translation.php' => config_path('translation.php'),
        ], 'config');

        $this->loadViewsFrom(__DIR__ . '/resources/views', 'translation');

        $this->registerRoutes();
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/config/translation.php', 'translation');
    }

    private function registerRoutes()
    {
        $this->app['router']->group(config('translation.route_group_config'), function ($router) {
            // $router->get('/translation', 'TranslationController@index');
        });
    }
}
