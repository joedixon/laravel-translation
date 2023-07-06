<?php

namespace Tests\Cases;

use JoeDixon\Translation\TranslationServiceProvider;
use Orchestra\Testbench\TestCase;

class EloquentProviderTestCase extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            TranslationServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        $app->useLangPath(__DIR__.'/../lang');
    }
}
