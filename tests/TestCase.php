<?php

namespace JoeDixon\Translation\Tests;

use JoeDixon\Translation\TranslationBindingsServiceProvider;
use JoeDixon\Translation\TranslationServiceProvider;
use Orchestra\Testbench\TestCase as TestbenchTestCase;

class TestCase extends TestbenchTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            TranslationServiceProvider::class,
            TranslationBindingsServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        $app['path.lang'] = __DIR__.'/fixtures/lang';
    }
}
