<?php

use Orchestra\Testbench\TestCase;
use JoeDixon\Translation\Drivers\File;
use JoeDixon\Translation\Exceptions\LanguageExistsException;

class FileDriverTest extends TestCase
{
    private $translation;

    /**
     * Setup the test environment.
     */
    protected function setUp()
    {
        parent::setUp();
        app()['path.lang'] = __DIR__ . '/fixtures/lang';
        $this->translation = app()->make('translation');
    }

    protected function getPackageProviders($app)
    {
        return ['JoeDixon\Translation\TranslationServiceProvider'];
    }

    /** @test */
    public function it_returns_all_languages()
    {
        $languages = $this->translation->allLanguages();

        $this->assertEquals(count($languages), 2);
        $this->assertEquals($languages, ['en', 'es']);
    }

    /** @test */
    public function it_returns_all_translations()
    {
        $translations = $this->translation->allTranslations();

        $this->assertEquals(count($translations), 2);
        $this->assertArraySubset(['en' => ['json' => ['Hello' => 'Hello', "What's up" => "What's up!"], 'array' => ['test' => ['hello' => 'Hello', 'whats_up' => "What's up!"]]]], $translations);
        $this->assertArrayHasKey('en', $translations);
        $this->assertArrayHasKey('es', $translations);
    }

    /** @test */
    public function it_returns_all_translations_for_a_given_language()
    {
        $translations = $this->translation->allTranslationsFor('en');
        $this->assertEquals(count($translations), 2);
        $this->assertEquals(['json' => ['Hello' => 'Hello', "What's up" => "What's up!"], 'array' => ['test' => ['hello' => 'Hello', 'whats_up' => "What's up!"]]], $translations);
        $this->assertArrayHasKey('json', $translations);
        $this->assertArrayHasKey('array', $translations);
    }

    /** @test */
    public function it_throws_an_exception_if_a_language_exists()
    {
        $this->expectException(LanguageExistsException::class);
        $this->translation->addLanguage('en');
    }

    /** @test */
    public function it_can_add_a_new_language()
    {
        $this->translation->addLanguage('fr');

        $this->assertTrue(file_exists(__DIR__ . '/fixtures/lang/fr.json'));
        $this->assertTrue(file_exists(__DIR__ . '/fixtures/lang/fr'));

        rmdir(__DIR__ . '/fixtures/lang/fr');
        unlink(__DIR__ . '/fixtures/lang/fr.json');
    }

    /** @test */
    public function it_can_add_a_new_translation_to_an_array_translation_file()
    {
        $this->translation->addArrayTranslation('es', 'test.hello', 'Hola!');

        $translations = $this->translation->allTranslationsFor('es');

        $this->assertArraySubset(['array' => ['test' => ['hello' => 'Hola!']]], $translations);

        unlink(__DIR__ . '/fixtures/lang/es/test.php');
    }

    /** @test */
    public function it_can_add_a_new_translation_to_an_existing_array_translation_file()
    {
        $this->translation->addArrayTranslation('en', 'test.test', 'Testing');

        $translations = $this->translation->allTranslationsFor('en');

        $this->assertArraySubset(['array' => ['test' => ['hello' => 'Hello', 'whats_up' => 'What\'s up!', 'test' => 'Testing']]], $translations);

        file_put_contents(
            app()['path.lang'] . '/en/test.php',
            "<?php\n\nreturn " . var_export(['hello' => 'Hello', 'whats_up' => 'What\'s up!'], true) . ';' . \PHP_EOL
        );
    }

    /** @test */
    public function it_can_add_a_new_translation_to_a_json_translation_file()
    {
        $this->translation->addJsonTranslation('es', 'Hello', 'Hola!');

        $translations = $this->translation->allTranslationsFor('es');

        $this->assertArraySubset(['json' => ['Hello' => 'Hola!']], $translations);

        unlink(__DIR__ . '/fixtures/lang/es.json');
    }

    /** @test */
    public function it_can_add_a_new_translation_to_an_existing_json_translation_file()
    {
        $this->translation->addJsonTranslation('en', 'Test', 'Testing');

        $translations = $this->translation->allTranslationsFor('en');

        $this->assertArraySubset(['json' => ['Hello' => 'Hello', 'What\'s up' => 'What\'s up!', 'Test' => 'Testing']], $translations);

        file_put_contents(
            app()['path.lang'] . '/en.json',
            json_encode((object)['Hello' => 'Hello', 'What\'s up' => 'What\'s up!'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
    }
}
