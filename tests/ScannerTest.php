<?php

namespace JoeDixon\Translation\Tests;

use JoeDixon\Translation\Scanner;
use JoeDixon\Translation\TranslationBindingsServiceProvider;
use JoeDixon\Translation\TranslationServiceProvider;
use Orchestra\Testbench\TestCase;

class ScannerTest extends TestCase
{
    private $scanner;

    protected function getPackageProviders($app)
    {
        return [
            TranslationServiceProvider::class,
            TranslationBindingsServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('translation.scan_paths', __DIR__.'/fixtures/scan-tests');
        $app['config']->set('translation.translation_methods', ['__', 'trans', 'trans_choice', '@lang', 'Lang::get']);
    }

    /** @test */
    public function it_finds_all_translations()
    {
        $this->scanner = app()->make(Scanner::class);
        $matches = $this->scanner->findTranslations();

        $this->assertEquals($matches, ['single' => ['single' => ['This will go in the JSON array' => '', 'trans' => '']], 'group' => ['lang' => ['first_match' => ''], 'lang_get' => ['first' => '', 'second' => ''], 'trans' => ['first_match' => '', 'third_match' => ''], 'trans_choice' => ['with_params' => '']]]);
        $this->assertCount(2, $matches);
    }
}
