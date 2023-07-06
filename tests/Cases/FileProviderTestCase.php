<?php

namespace Tests\Cases;

use JoeDixon\Translation\TranslationServiceProvider;
use JoeDixon\TranslationCore\TranslationServiceProvider as TranslationCoreServiceProvider;
use Orchestra\Testbench\TestCase;

class FileProviderTestCase extends TestCase
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
