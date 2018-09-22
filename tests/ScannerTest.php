<?php

use JoeDixon\Translation\Scanner;
use Orchestra\Testbench\TestCase;

class ScannerTest extends TestCase
{
    private $scanner;

    /**
     * Setup the test environment.
     */
    protected function setUp()
    {
        parent::setUp();
        config(['translation.scan_paths' => __DIR__.'/fixtures/scan-tests']);
    }

    protected function getPackageProviders($app)
    {
        return ['JoeDixon\Translation\TranslationServiceProvider'];
    }

    /** @test */
    public function it_finds_all_translations()
    {
        config(['translation.translation_methods' => ['__', 'trans', 'trans_choice', '@lang', 'Lang::get']]);

        $this->scanner = app()->make(Scanner::class);
        $matches = $this->scanner->findTranslations();

        $this->assertEquals($matches, ['single' => ['This will go in the JSON array' => '', 'trans' => ''], 'group' => ['lang' => ['first_match' => ''], 'lang_get' => ['first' => '', 'second' => ''], 'trans' => ['first_match' => '', 'third_match' => ''], 'trans_choice' => ['with_params' => '']]]);
        $this->assertCount(2, $matches);
    }
}
