<?php

namespace JoeDixon\Translation\Tests;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Event;
use JoeDixon\Translation\Drivers\Translation;
use JoeDixon\Translation\Events\TranslationAdded;
use JoeDixon\Translation\Exceptions\LanguageExistsException;
use JoeDixon\Translation\Language;
use JoeDixon\Translation\Translation as TranslationModel;
use JoeDixon\Translation\TranslationBindingsServiceProvider;
use JoeDixon\Translation\TranslationServiceProvider;
use Orchestra\Testbench\TestCase;

class DatabaseDriverTest extends TestCase
{
    use DatabaseMigrations;

    private $translation;

    /**
     * Setup the test environment.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->translation = $this->app[Translation::class];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('translation.driver', 'database');
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
    }

    protected function getPackageProviders($app)
    {
        return [
            TranslationServiceProvider::class,
            TranslationBindingsServiceProvider::class,
        ];
    }

    /** @test */
    public function it_returns_all_languages()
    {
        $newLanguages = Language::factory(2)->create();
        $newLanguages = $newLanguages->mapWithKeys(function ($language) {
            return [$language->language => $language->name];
        })->toArray();
        $languages = $this->translation->allLanguages();

        $this->assertEquals($languages->count(), 3);
        $this->assertEquals($languages->toArray(), ['en' => 'en'] + $newLanguages);
    }

    /** @test */
    public function it_returns_all_translations()
    {
        $default = Language::where('language', config('app.locale'))->first();
        Language::factory()->create(['language' => 'es', 'name' => 'Español']);
        TranslationModel::factory()->shortKey()->create(['language_id' => $default->id, 'group' => 'test', 'key' => 'hello', 'value' => 'Hello']);
        TranslationModel::factory()->shortKey()->create(['language_id' => $default->id, 'group' => 'test', 'key' => 'whats_up', 'value' => "What's up!"]);
        TranslationModel::factory()->stringKey()->create(['language_id' => $default->id, 'group' => 'string', 'key' => 'Hello', 'value' => 'Hello']);
        TranslationModel::factory()->stringKey()->create(['language_id' => $default->id, 'group' => 'string', 'key' => "What's up", 'value' => "What's up!"]);

        $translations = $this->translation->allTranslations();

        $this->assertEquals($translations->count(), 2);
        $this->assertEquals(['string' => ['string' => ['Hello' => 'Hello', "What's up" => "What's up!"]], 'short' => ['test' => ['hello' => 'Hello', 'whats_up' => "What's up!"]]], $translations->toArray()['en']);
        $this->assertArrayHasKey('en', $translations->toArray());
        $this->assertArrayHasKey('es', $translations->toArray());
    }

    /** @test */
    public function it_returns_all_translations_for_a_given_language()
    {
        $default = Language::where('language', config('app.locale'))->first();
        TranslationModel::factory()->shortKey()->create(['language_id' => $default->id, 'group' => 'test', 'key' => 'hello', 'value' => 'Hello']);
        TranslationModel::factory()->shortKey()->create(['language_id' => $default->id, 'group' => 'test', 'key' => 'whats_up', 'value' => "What's up!"]);
        TranslationModel::factory()->stringKey()->create(['language_id' => $default->id, 'group' => 'string', 'key' => 'Hello', 'value' => 'Hello']);
        TranslationModel::factory()->stringKey()->create(['language_id' => $default->id, 'group' => 'string', 'key' => "What's up", 'value' => "What's up!"]);

        $translations = $this->translation->allTranslationsFor('en');
        $this->assertEquals($translations->count(), 2);
        $this->assertEquals(['string' => ['string' => ['Hello' => 'Hello', "What's up" => "What's up!"]], 'short' => ['test' => ['hello' => 'Hello', 'whats_up' => "What's up!"]]], $translations->toArray());
        $this->assertArrayHasKey('string', $translations->toArray());
        $this->assertArrayHasKey('short', $translations->toArray());
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
        $this->assertDatabaseMissing(config('translation.database.languages_table'), [
            'language' => 'fr',
            'name' => 'Français',
        ]);

        $this->translation->addLanguage('fr', 'Français');
        $this->assertDatabaseHas(config('translation.database.languages_table'), [
            'language' => 'fr',
            'name' => 'Français',
        ]);
    }

    /** @test */
    public function it_can_add_a_new_translation_to_a_new_group()
    {
        $this->translation->addShortKeyTranslation('es', 'test', 'hello', 'Hola!');

        $translations = $this->translation->allTranslationsFor('es');

        $this->assertEquals(['test' => ['hello' => 'Hola!']], $translations->toArray()['short']);
    }

    /** @test */
    public function it_can_add_a_new_translation_to_an_existing_translation_group()
    {
        $translation = TranslationModel::factory()->create();

        $this->translation->addShortKeyTranslation($translation->language->language, "{$translation->group}", 'test', 'Testing');

        $translations = $this->translation->allTranslationsFor($translation->language->language);
        $this->assertSame([$translation->group => [$translation->key => $translation->value, 'test' => 'Testing']], $translations->toArray()['short']);
    }

    /** @test */
    public function it_can_add_a_new_short_key_translation()
    {
        $this->translation->addStringKeyTranslation('es', 'string', 'Hello', 'Hola!');

        $translations = $this->translation->allTranslationsFor('es');

        $this->assertEquals(['string' => ['Hello' => 'Hola!']], $translations->toArray()['string']);
    }

    /** @test */
    public function it_can_add_a_new_short_key_translation_to_an_existing_language()
    {
        $translation = TranslationModel::factory()->stringKey()->create();

        $this->translation->addStringKeyTranslation($translation->language->language, 'string', 'Test', 'Testing');

        $translations = $this->translation->allTranslationsFor($translation->language->language);

        $this->assertEquals(['string' => ['Test' => 'Testing', $translation->key => $translation->value]], $translations->toArray()['string']);
    }

    /** @test */
    public function it_can_get_a_collection_of_group_names_for_a_given_language()
    {
        $language = Language::factory()->create(['language' => 'en']);
        TranslationModel::factory()->create([
            'language_id' => $language->id,
            'group' => 'test',
        ]);

        $groups = $this->translation->allShortKeyGroupsFor('en');

        $this->assertEquals($groups->toArray(), ['test']);
    }

    /** @test */
    public function it_can_merge_a_language_with_the_base_language()
    {
        $default = Language::where('language', config('app.locale'))->first();
        TranslationModel::factory()->shortKey()->create(['language_id' => $default->id, 'group' => 'test', 'key' => 'hello', 'value' => 'Hello']);
        TranslationModel::factory()->shortKey()->create(['language_id' => $default->id, 'group' => 'test', 'key' => 'whats_up', 'value' => "What's up!"]);
        TranslationModel::factory()->stringKey()->create(['language_id' => $default->id, 'group' => 'string', 'key' => 'Hello', 'value' => 'Hello']);
        TranslationModel::factory()->stringKey()->create(['language_id' => $default->id, 'group' => 'string', 'key' => "What's up", 'value' => "What's up!"]);

        $this->translation->addShortKeyTranslation('es', 'test', 'hello', 'Hola!');
        $translations = $this->translation->getSourceLanguageTranslationsWith('es');

        $this->assertEquals($translations->toArray(), [
            'short' => [
                'test' => [
                    'hello' => ['en' => 'Hello', 'es' => 'Hola!'],
                    'whats_up' => ['en' => "What's up!", 'es' => ''],
                ],
            ],
            'string' => [
                'string' => [
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
    }

    /** @test */
    public function it_can_add_a_vendor_namespaced_translations()
    {
        $this->translation->addShortKeyTranslation('es', 'translation_test::test', 'hello', 'Hola!');

        $this->assertEquals($this->translation->allTranslationsFor('es')->toArray(), [
            'short' => [
                'translation_test::test' => [
                    'hello' => 'Hola!',
                ],
            ],
            'string' => [],
        ]);
    }

    /** @test */
    public function it_can_add_a_nested_translation()
    {
        $this->translation->addShortKeyTranslation('en', 'test', 'test.nested', 'Nested!');

        $this->assertEquals($this->translation->allShortKeyTranslationsFor('en')->toArray(), [
            'test' => [
                'test.nested' => 'Nested!',
            ],
        ]);
    }

    /** @test */
    public function it_can_add_nested_vendor_namespaced_translations()
    {
        $this->translation->addShortKeyTranslation('es', 'translation_test::test', 'nested.hello', 'Hola!');

        $this->assertEquals($this->translation->allTranslationsFor('es')->toArray(), [
            'short' => [
                'translation_test::test' => [
                    'nested.hello' => 'Hola!',
                ],
            ],
            'string' => [],
        ]);
    }

    /** @test */
    public function it_can_merge_a_namespaced_language_with_the_base_language()
    {
        $this->translation->addShortKeyTranslation('en', 'translation_test::test', 'hello', 'Hello');
        $this->translation->addShortKeyTranslation('es', 'translation_test::test', 'hello', 'Hola!');
        $translations = $this->translation->getSourceLanguageTranslationsWith('es');

        $this->assertEquals($translations->toArray(), [
            'short' => [
                'translation_test::test' => [
                    'hello' => ['en' => 'Hello', 'es' => 'Hola!'],
                ],
            ],
            'string' => [],
        ]);
    }

    /** @test */
    public function a_list_of_languages_can_be_viewed()
    {
        $newLanguages = Language::factory(2)->create();
        $response = $this->get(config('translation.ui_url'));

        $response->assertSee(config('app.locale'));
        foreach ($newLanguages as $language) {
            $response->assertSee($language->language);
        }
    }

    /** @test */
    public function the_language_creation_page_can_be_viewed()
    {
        $this->translation->addShortKeyTranslation(config('app.locale'), 'translation::translation', 'add_language', 'Add a new language');
        $this->get(config('translation.ui_url') . '/create')
            ->assertSee('Add a new language');
    }

    /** @test */
    public function a_language_can_be_added()
    {
        $this->post(config('translation.ui_url'), ['locale' => 'de'])
            ->assertRedirect();

        $this->assertDatabaseHas('languages', ['language' => 'de']);
    }

    /** @test */
    public function a_list_of_translations_can_be_viewed()
    {
        $default = Language::where('language', config('app.locale'))->first();
        TranslationModel::factory()->shortKey()->create(['language_id' => $default->id, 'group' => 'test', 'key' => 'hello', 'value' => 'Hello']);
        TranslationModel::factory()->shortKey()->create(['language_id' => $default->id, 'group' => 'test', 'key' => 'whats_up', 'value' => "What's up!"]);
        TranslationModel::factory()->stringKey()->create(['language_id' => $default->id, 'key' => 'Hello', 'value' => 'Hello!']);
        TranslationModel::factory()->stringKey()->create(['language_id' => $default->id, 'key' => "What's up", 'value' => 'Sup!']);

        $this->get(config('translation.ui_url') . '/en/translations')
            ->assertSee('hello')
            ->assertSee('whats_up')
            ->assertSee('Hello')
            ->assertSee('Sup!');
    }

    /** @test */
    public function the_translation_creation_page_can_be_viewed()
    {
        $this->translation->addShortKeyTranslation('en', 'translation::translation', 'add_translation', 'Add a translation');
        $this->get(config('translation.ui_url') . '/' . config('app.locale') . '/translations/create')
            ->assertSee('Add a translation');
    }

    /** @test */
    public function a_new_translation_can_be_added()
    {
        $this->post(config('translation.ui_url') . '/' . config('app.locale') . '/translations', ['group' => 'single', 'key' => 'joe', 'value' => 'is cool'])
            ->assertRedirect();

        $this->assertDatabaseHas('translations', ['language_id' => 1, 'key' => 'joe', 'value' => 'is cool']);
    }

    /** @test */
    public function a_translation_can_be_updated()
    {
        $default = Language::where('language', config('app.locale'))->first();
        TranslationModel::factory()->shortKey()->create(['language_id' => $default->id, 'group' => 'test', 'key' => 'hello', 'value' => 'Hello']);
        $this->assertDatabaseHas('translations', ['language_id' => 1, 'group' => 'test', 'key' => 'hello', 'value' => 'Hello']);

        $this->post(config('translation.ui_url') . '/en', ['group' => 'test', 'key' => 'hello', 'value' => 'Hello there!'])
            ->assertStatus(200);

        $this->assertDatabaseHas('translations', ['language_id' => 1, 'group' => 'test', 'key' => 'hello', 'value' => 'Hello there!']);
    }

    /** @test */
    public function adding_a_translation_fires_an_event_with_the_expected_data()
    {
        Event::fake();

        $data = ['key' => 'joe', 'value' => 'is cool'];
        $this->post(config('translation.ui_url') . '/en/translations', $data);

        Event::assertDispatched(TranslationAdded::class, function ($event) use ($data) {
            return $event->language === 'en' &&
                $event->group === 'string' &&
                $event->value === $data['value'] &&
                $event->key === $data['key'];
        });
    }

    /** @test */
    public function updating_a_translation_fires_an_event_with_the_expected_data()
    {
        Event::fake();

        $data = ['group' => 'test', 'key' => 'hello', 'value' => 'Hello there!'];
        $this->post(config('translation.ui_url') . '/en/translations', $data);

        Event::assertDispatched(TranslationAdded::class, function ($event) use ($data) {
            return $event->language === 'en' &&
                $event->group === $data['group'] &&
                $event->value === $data['value'] &&
                $event->key === $data['key'];
        });
    }
}
