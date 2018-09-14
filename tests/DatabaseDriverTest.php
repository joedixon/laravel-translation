<?php

use Orchestra\Testbench\TestCase;
use JoeDixon\Translation\Language;
use JoeDixon\Translation\Drivers\Database;
use JoeDixon\Translation\Drivers\Translation;
use JoeDixon\Translation\TranslationServiceProvider;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use JoeDixon\Translation\Translation as TranslationModel;
use JoeDixon\Translation\Exceptions\LanguageExistsException;

class DatabaseDriverTest extends TestCase
{
    use DatabaseMigrations;

    private $translation;

    /**
     * Setup the test environment.
     */
    public function setUp()
    {
        parent::setUp();
        $this->withFactories(__DIR__ . '/../database/factories');
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('translation.driver', 'database');
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:'
        ]);

        $this->translation = $app[Translation::class];
    }

    protected function getPackageProviders($app)
    {
        return [TranslationServiceProvider::class];
    }

    /** @test */
    public function it_returns_all_languages()
    {
        $newLanguages = factory(Language::class, 2)->create();
        $newLanguages = $newLanguages->mapWithKeys(function ($language) {
            return [$language->language => $language->name];
        })->toArray();
        $languages = $this->translation->allLanguages();

        $this->assertEquals($languages->count(), 2);
        $this->assertEquals($languages->toArray(), $newLanguages);
    }

    /** @test */
    public function it_returns_all_translations()
    {
        $english = factory(Language::class)->create(['language' => 'en', 'name' => 'English']);
        $spanish = factory(Language::class)->create(['language' => 'es', 'name' => 'Español']);
        factory(TranslationModel::class)->states('group')->create(['language_id' => $english->id, 'group' => 'test', 'key' => 'hello', 'value' => 'Hello']);
        factory(TranslationModel::class)->states('group')->create(['language_id' => $english->id, 'group' => 'test', 'key' => 'whats_up', 'value' => "What's up!"]);
        factory(TranslationModel::class)->states('single')->create(['language_id' => $english->id, 'key' => 'Hello', 'value' => 'Hello']);
        factory(TranslationModel::class)->states('single')->create(['language_id' => $english->id, 'key' => "What's up", 'value' => "What's up!"]);

        $translations = $this->translation->allTranslations();

        $this->assertEquals($translations->count(), 2);
        $this->assertArraySubset(['en' => ['single' => ['Hello' => 'Hello', "What's up" => "What's up!"], 'group' => ['test' => ['hello' => 'Hello', 'whats_up' => "What's up!"]]]], $translations->toArray());
        $this->assertArrayHasKey('en', $translations->toArray());
        $this->assertArrayHasKey('es', $translations->toArray());
    }

    /** @test */
    public function it_returns_all_translations_for_a_given_language()
    {
        $english = factory(Language::class)->create(['language' => 'en', 'name' => 'English']);
        factory(TranslationModel::class)->states('group')->create(['language_id' => $english->id, 'group' => 'test', 'key' => 'hello', 'value' => 'Hello']);
        factory(TranslationModel::class)->states('group')->create(['language_id' => $english->id, 'group' => 'test', 'key' => 'whats_up', 'value' => "What's up!"]);
        factory(TranslationModel::class)->states('single')->create(['language_id' => $english->id, 'key' => 'Hello', 'value' => 'Hello']);
        factory(TranslationModel::class)->states('single')->create(['language_id' => $english->id, 'key' => "What's up", 'value' => "What's up!"]);

        $translations = $this->translation->allTranslationsFor('en');
        $this->assertEquals($translations->count(), 2);
        $this->assertEquals(['single' => ['Hello' => 'Hello', "What's up" => "What's up!"], 'group' => ['test' => ['hello' => 'Hello', 'whats_up' => "What's up!"]]], $translations->toArray());
        $this->assertArrayHasKey('single', $translations->toArray());
        $this->assertArrayHasKey('group', $translations->toArray());
    }

    /** @test */
    public function it_throws_an_exception_if_a_language_exists()
    {
        factory(Language::class)->create(['language' => 'en']);

        $this->expectException(LanguageExistsException::class);
        $this->translation->addLanguage('en');
    }

    /** @test */
    public function it_can_add_a_new_language()
    {
        $this->assertDatabaseMissing(config('translation.database.languages_table'), [
            'language' => 'fr',
            'name' => 'Français'
        ]);

        $this->translation->addLanguage('fr', 'Français');
        $this->assertDatabaseHas(config('translation.database.languages_table'), [
            'language' => 'fr',
            'name' => 'Français'
        ]);
    }

    /** @test */
    public function it_can_add_a_new_translation_to_a_new_group()
    {
        $this->translation->addGroupTranslation('es', 'test.hello', 'Hola!');

        $translations = $this->translation->allTranslationsFor('es');

        $this->assertArraySubset(['group' => ['test' => ['hello' => 'Hola!']]], $translations->toArray());
    }

    /** @test */
    public function it_can_add_a_new_translation_to_an_existing_translation_group()
    {
        $translation = factory(TranslationModel::class)->create();

        $this->translation->addGroupTranslation($translation->language->language, "{$translation->group}.test", 'Testing');

        $translations = $this->translation->allTranslationsFor($translation->language->language);

        $this->assertArraySubset(['group' => [$translation->group => [$translation->key => $translation->value, 'test' => 'Testing']]], $translations->toArray());
    }

    /** @test */
    public function it_can_add_a_new_single_translation()
    {
        $this->translation->addSingleTranslation('es', 'Hello', 'Hola!');

        $translations = $this->translation->allTranslationsFor('es');

        $this->assertArraySubset(['single' => ['Hello' => 'Hola!']], $translations->toArray());
    }

    /** @test */
    public function it_can_add_a_new_single_translation_to_an_existing_language()
    {
        $translation = factory(TranslationModel::class)->states('single')->create();

        $this->translation->addSingleTranslation($translation->language->language, 'Test', 'Testing');

        $translations = $this->translation->allTranslationsFor($translation->language->language);

        $this->assertArraySubset(['single' => ['Test' => 'Testing', $translation->key => $translation->value]], $translations->toArray());
    }

    /** @test */
    public function it_can_get_a_collection_of_group_names_for_a_given_language()
    {
        $language = factory(Language::class)->create(['language' => 'en']);
        factory(TranslationModel::class)->create([
            'language_id' => $language->id,
            'group' => 'test'
        ]);

        $groups = $this->translation->getGroupsFor('en');

        $this->assertEquals($groups->toArray(), ['test']);
    }

    /** @test */
    public function it_can_merge_a_language_with_the_base_language()
    {
        $english = factory(Language::class)->create(['language' => 'en']);
        factory(TranslationModel::class)->states('group')->create(['language_id' => $english->id, 'group' => 'test', 'key' => 'hello', 'value' => 'Hello']);
        factory(TranslationModel::class)->states('group')->create(['language_id' => $english->id, 'group' => 'test', 'key' => 'whats_up', 'value' => "What's up!"]);
        factory(TranslationModel::class)->states('single')->create(['language_id' => $english->id, 'key' => 'Hello', 'value' => 'Hello']);
        factory(TranslationModel::class)->states('single')->create(['language_id' => $english->id, 'key' => "What's up", 'value' => "What's up!"]);

        $this->translation->addGroupTranslation('es', 'test.hello', 'Hola!');
        $translations = $this->translation->getSourceLanguageTranslationsWith('es');

        $this->assertEquals($translations->toArray(), [
            'group' => [
                'test' => [
                    'hello' => ['en' => 'Hello', 'es' => 'Hola!'],
                    'whats_up' => ['en' => "What's up!", 'es' => '']
                ]
            ],
            'single' => [
                'Hello' => [
                    'en' => 'Hello',
                    'es' => ''
                ],
                "What's up" => [
                    'en' => "What's up!",
                    'es' => ''
                ]
            ]
        ]);
    }

    /** @test */
    public function it_can_add_a_vendor_namespaced_translations()
    {
        $this->translation->addGroupTranslation('es', 'translation_test::test.hello', 'Hola!');

        $this->assertEquals($this->translation->allTranslationsFor('es')->toArray(), [
            'group' => [
                'translation_test::test' => [
                    'hello' => 'Hola!'
                ]
            ],
            'single' => []
        ]);
    }

    /** @test */
    public function it_can_merge_a_namespaced_language_with_the_base_language()
    {
        $this->translation->addGroupTranslation('en', 'translation_test::test.hello', 'Hello');
        $this->translation->addGroupTranslation('es', 'translation_test::test.hello', 'Hola!');
        $translations = $this->translation->getSourceLanguageTranslationsWith('es');

        $this->assertEquals($translations->toArray(), [
            'group' => [
                'translation_test::test' => [
                    'hello' => ['en' => 'Hello', 'es' => 'Hola!']
                ]
            ],
            'single' => []
        ]);
    }
}
