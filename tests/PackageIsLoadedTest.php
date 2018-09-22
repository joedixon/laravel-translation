<?php

namespace JoeDixon\Translation\Tests;

use Orchestra\Testbench\TestCase;

class PackageIsLoadedTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return ['JoeDixon\Translation\TranslationServiceProvider'];
    }

    /** @test */
    public function the_translation_pacakage_is_loaded()
    {
        $this->assertArrayHasKey('JoeDixon\Translation\TranslationServiceProvider', app()->getLoadedProviders());
    }
}
