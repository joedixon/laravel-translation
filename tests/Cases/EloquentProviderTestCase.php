<?php

namespace Tests\Cases;

use JoeDixon\Translation\TranslationBindingsServiceProvider;
use JoeDixon\Translation\TranslationServiceProvider;
use JoeDixon\TranslationCore\TranslationServiceProvider as TranslationCoreServiceProvider;
use Orchestra\Testbench\TestCase;

class EloquentProviderTestCase extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            TranslationCoreServiceProvider::class,
            TranslationBindingsServiceProvider::class,
            TranslationServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        $app->useLangPath(__DIR__.'/../lang');
        $app->config->set('translation.driver', 'eloquent');
    }
}
