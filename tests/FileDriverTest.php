<?php

namespace JoeDixon\Translation\Tests;

use JoeDixon\Translation\Drivers\Translation;
use JoeDixon\Translation\Exceptions\LanguageExistsException;
use JoeDixon\Translation\TranslationBindingsServiceProvider;
use JoeDixon\Translation\TranslationServiceProvider;
use Orchestra\Testbench\TestCase;

class FileDriverTest extends TestCase
{
    private $translation;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        app()['path.lang'] = __DIR__.'/fixtures/lang';
        $this->translation = app()->make(Translation::class);
    }

    protected function getPackageProviders($app)
    {
        return [
            TranslationServiceProvider::class,
            TranslationBindingsServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('translation.driver', 'file');
    }

    /** @test */
    public function it_returns_all_languages()
    {
        $languages = $this->translation->allLanguages();

        $this->assertEquals($languages->count(), 2);
        $this->assertEquals($languages->toArray(), ['en' => 'en', 'es' => 'es']);
    }

    /** @test */
    public function it_returns_all_translations()
    {
        $translations = $this->translation->allTranslations();

        $this->assertEquals($translations->count(), 2);
        $this->assertArraySubset(['en' => ['single' => ['single' => ['Hello' => 'Hello', "What's up" => "What's up!"]], 'group' => ['test' => ['hello' => 'Hello', 'whats_up' => "What's up!"]]]], $translations->toArray());
        $this->assertArrayHasKey('en', $translations->toArray());
        $this->assertArrayHasKey('es', $translations->toArray());
    }

    /** @test */
    public function it_returns_all_translations_for_a_given_language()
    {
        $translations = $this->translation->allTranslationsFor('en');
        $this->assertEquals($translations->count(), 2);
        $this->assertEquals(['single' => ['single' => ['Hello' => 'Hello', "What's up" => "What's up!"]], 'group' => ['test' => ['hello' => 'Hello', 'whats_up' => "What's up!"]]], $translations->toArray());
        $this->assertArrayHasKey('single', $translations->toArray());
        $this->assertArrayHasKey('group', $translations->toArray());
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

        $this->assertTrue(file_exists(__DIR__.'/fixtures/lang/fr.json'));
        $this->assertTrue(file_exists(__DIR__.'/fixtures/lang/fr'));

        rmdir(__DIR__.'/fixtures/lang/fr');
        unlink(__DIR__.'/fixtures/lang/fr.json');
    }

    /** @test */
    public function it_can_add_a_new_translation_to_a_new_group()
    {
        $this->translation->addGroupTranslation('es', 'test', 'hello', 'Hola!');

        $translations = $this->translation->allTranslationsFor('es');

        $this->assertArraySubset(['group' => ['test' => ['hello' => 'Hola!']]], $translations->toArray());

        unlink(__DIR__.'/fixtures/lang/es/test.php');
    }

    /** @test */
    public function it_can_add_a_new_translation_to_an_existing_translation_group()
    {
        $this->translation->addGroupTranslation('en', 'test', 'test', 'Testing');

        $translations = $this->translation->allTranslationsFor('en');

        $this->assertArraySubset(['group' => ['test' => ['hello' => 'Hello', 'whats_up' => 'What\'s up!', 'test' => 'Testing']]], $translations->toArray());

        file_put_contents(
            app()['path.lang'].'/en/test.php',
            "<?php\n\nreturn ".var_export(['hello' => 'Hello', 'whats_up' => 'What\'s up!'], true).';'.\PHP_EOL
        );
    }

    /** @test */
    public function it_can_add_a_new_single_translation()
    {
        $this->translation->addSingleTranslation('es', 'single', 'Hello', 'Hola!');

        $translations = $this->translation->allTranslationsFor('es');

        $this->assertArraySubset(['single' => ['single' => ['Hello' => 'Hola!']]], $translations->toArray());

        unlink(__DIR__.'/fixtures/lang/es.json');
    }

    /** @test */
    public function it_can_add_a_new_single_translation_to_an_existing_language()
    {
        $this->translation->addSingleTranslation('en', 'single', 'Test', 'Testing');

        $translations = $this->translation->allTranslationsFor('en');

        $this->assertArraySubset(['single' => ['single' => ['Hello' => 'Hello', 'What\'s up' => 'What\'s up!', 'Test' => 'Testing']]], $translations->toArray());

        file_put_contents(
            app()['path.lang'].'/en.json',
            json_encode((object) ['Hello' => 'Hello', 'What\'s up' => 'What\'s up!'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
    }

    /** @test */
    public function it_can_get_a_collection_of_group_names_for_a_given_language()
    {
        $groups = $this->translation->getGroupsFor('en');

        $this->assertEquals($groups->toArray(), ['test']);
    }

    /** @test */
    public function it_can_merge_a_language_with_the_base_language()
    {
        $this->translation->addGroupTranslation('es', 'test', 'hello', 'Hola!');
        $translations = $this->translation->getSourceLanguageTranslationsWith('es');

        $this->assertEquals($translations->toArray(), [
            'group' => [
                'test' => [
                    'hello' => ['en' => 'Hello', 'es' => 'Hola!'],
                    'whats_up' => ['en' => "What's up!", 'es' => ''],
                ],
            ],
            'single' => [
                'single' => [
                    'Hello' => [
                        'en' => 'Hello',
                        'es' => '',
                    ],
                    "What's up" => [
                        'en' => "What's up!",
                        'es' => '',
                    ],
                ],
            ],
        ]);

        unlink(__DIR__.'/fixtures/lang/es/test.php');
    }

    /** @test */
    public function it_can_add_a_vendor_namespaced_translations()
    {
        $this->translation->addGroupTranslation('es', 'translation_test::test', 'hello', 'Hola!');

        $this->assertEquals($this->translation->allTranslationsFor('es')->toArray(), [
            'group' => [
                'translation_test::test' => [
                    'hello' => 'Hola!',
                ],
            ],
            'single' => [],
        ]);

        \File::deleteDirectory(__DIR__.'/fixtures/lang/vendor');
    }

    /** @test */
    public function it_can_add_a_nested_translation()
    {
        $this->translation->addGroupTranslation('en', 'test', 'test.nested', 'Nested!');

        $this->assertEquals($this->translation->getGroupTranslationsFor('en')->toArray(), [
            'test' => [
                'hello' => 'Hello',
                'test.nested' => 'Nested!',
                'whats_up' => 'What\'s up!',
            ],
        ]);

        file_put_contents(
            app()['path.lang'].'/en/test.php',
            "<?php\n\nreturn ".var_export(['hello' => 'Hello', 'whats_up' => 'What\'s up!'], true).';'.\PHP_EOL
        );
    }

    /** @test */
    public function it_can_add_nested_vendor_namespaced_translations()
    {
        $this->translation->addGroupTranslation('es', 'translation_test::test', 'nested.hello', 'Hola!');

        $this->assertEquals($this->translation->allTranslationsFor('es')->toArray(), [
            'group' => [
                'translation_test::test' => [
                    'nested.hello' => 'Hola!',
                ],
            ],
            'single' => [],
        ]);

        \File::deleteDirectory(__DIR__.'/fixtures/lang/vendor');
    }

    /** @test */
    public function it_can_merge_a_namespaced_language_with_the_base_language()
    {
        $this->translation->addGroupTranslation('en', 'translation_test::test', 'hello', 'Hello');
        $this->translation->addGroupTranslation('es', 'translation_test::test', 'hello', 'Hola!');
        $translations = $this->translation->getSourceLanguageTranslationsWith('es');

        $this->assertEquals($translations->toArray(), [
            'group' => [
                'test' => [
                    'hello' => ['en' => 'Hello', 'es' => ''],
                    'whats_up' => ['en' => "What's up!", 'es' => ''],
                ],
                'translation_test::test' => [
                    'hello' => ['en' => 'Hello', 'es' => 'Hola!'],
                ],
            ],
            'single' => [
                'single' => [
                    'Hello' => [
                        'en' => 'Hello',
                        'es' => '',
                    ],
                    "What's up" => [
                        'en' => "What's up!",
                        'es' => '',
                    ],
                ],
            ],
        ]);

        \File::deleteDirectory(__DIR__.'/fixtures/lang/vendor');
    }

    /** @test */
    public function a_list_of_languages_can_be_viewed()
    {
        $this->get(config('translation.ui_url'))
            ->assertSee('en');
    }

    /** @test */
    public function the_language_creation_page_can_be_viewed()
    {
        $this->get(config('translation.ui_url').'/create')
            ->assertSee('Add a new language');
    }

    /** @test */
    public function a_language_can_be_added()
    {
        $this->post(config('translation.ui_url'), ['locale' => 'de'])
            ->assertRedirect();

        $this->assertTrue(file_exists(__DIR__.'/fixtures/lang/de.json'));
        $this->assertTrue(file_exists(__DIR__.'/fixtures/lang/de'));

        rmdir(__DIR__.'/fixtures/lang/de');
        unlink(__DIR__.'/fixtures/lang/de.json');
    }

    /** @test */
    public function a_list_of_translations_can_be_viewed()
    {
        $this->get(config('translation.ui_url').'/en/translations')
            ->assertSee('hello')
            ->assertSee('whats_up');
    }

    /** @test */
    public function the_translation_creation_page_can_be_viewed()
    {
        $this->get(config('translation.ui_url').'/'.config('app.locale').'/translations/create')
            ->assertSee('Add a translation');
    }

    /** @test */
    public function a_new_translation_can_be_added()
    {
        $this->post(config('translation.ui_url').'/en/translations', ['key' => 'joe', 'value' => 'is cool'])
            ->assertRedirect();
        $translations = $this->translation->getSingleTranslationsFor('en');

        $this->assertArraySubset(['single' => ['Hello' => 'Hello', 'What\'s up' => 'What\'s up!', 'joe' => 'is cool']], $translations->toArray());

        file_put_contents(
            app()['path.lang'].'/en.json',
            json_encode((object) ['Hello' => 'Hello', 'What\'s up' => 'What\'s up!'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
    }

    /** @test */
    public function a_translation_can_be_updated()
    {
        $this->post(config('translation.ui_url').'/en', ['group' => 'test', 'key' => 'hello', 'value' => 'Hello there!'])
            ->assertStatus(200)
            ->assertSee(json_encode(['success' => true]));
        $translations = $this->translation->getGroupTranslationsFor('en');

        $this->assertArraySubset(['test' => ['hello' => 'Hello there!', 'whats_up' => 'What\'s up!']], $translations->toArray());

        file_put_contents(
            app()['path.lang'].'/en/test.php',
            "<?php\n\nreturn ".var_export(['hello' => 'Hello', 'whats_up' => 'What\'s up!'], true).';'.\PHP_EOL
        );
    }
}
