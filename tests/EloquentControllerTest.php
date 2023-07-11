<?php

namespace JoeDixon\Translation\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use JoeDixon\TranslationCore\Events\TranslationAdded;
use JoeDixon\TranslationCore\Providers\Eloquent\Language;
use JoeDixon\TranslationCore\Providers\Eloquent\Translation;
use JoeDixon\TranslationCore\TranslationManager;
use Tests\Cases\EloquentProviderTestCase;

uses(EloquentProviderTestCase::class);
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->translation = $this->app->make(TranslationManager::class);
    File::deleteDirectory($this->app->langPath());
    File::copyDirectory(__DIR__.'/fixtures/lang', $this->app->langPath());
    Artisan::call('translation:sync-translations file eloquent');
    $this->translation = $this->app->make(TranslationManager::class);
});

afterEach(function () {
    File::deleteDirectory($this->app->langPath());
});

it('can list all languages', function () {
    $languages = $this->translation->languages();
    $response = $this->get('/languages');

    $response->assertSee(config('app.locale'));
    foreach ($languages as $language) {
        $response->assertSee($language);
    }
});

it('can render the language creation page', function () {
    $this->translation->addShortKeyTranslation(config('app.locale'), 'translation', 'add_language', 'Add a new language', 'translation');

    $this->get('languages/create')
        ->assertSee('Add a new language');
});

it('can add a new language', function () {
    $this->post('/languages', ['locale' => 'de'])
        ->assertRedirect();

    $this->assertDatabaseHas('languages', ['language' => 'de']);
});

it('can list all translations', function () {
    $default = Language::where('language', config('app.locale'))->first();
    Translation::factory()->shortKey()->create(['language_id' => $default->id, 'group' => 'test', 'key' => 'hello', 'value' => 'Hello']);
    Translation::factory()->shortKey()->create(['language_id' => $default->id, 'group' => 'test', 'key' => 'whats_up', 'value' => "What's up!"]);
    Translation::factory()->stringKey()->create(['language_id' => $default->id, 'key' => 'Hello', 'value' => 'Hello!']);
    Translation::factory()->stringKey()->create(['language_id' => $default->id, 'key' => "What's up", 'value' => 'Sup!']);

    $this->get('languages/en/translations')
        ->assertSee('hello')
        ->assertSee('whats_up')
        ->assertSee('Hello')
        ->assertSee('Sup!');
});

it('can render the translation creation page', function () {
    $this->translation->addShortKeyTranslation('en', 'translation', 'add_translation', 'Add a translation', 'translation');
    $this->get('languages/'.config('app.locale').'/translations/create')
        ->assertSee('Add a translation');
});

it('can add a new translation', function () {
    $this->post('languages/'.config('app.locale').'/translations', ['group' => null, 'key' => 'joe', 'value' => 'is cool'])
        ->assertRedirect();

    $this->assertDatabaseHas('translations', [
        'language_id' => Language::where('language', config('app.locale'))->first()->id,
        'key'         => 'joe',
        'value'       => 'is cool',
    ]);
});

it('can update a translation', function () {
    $default = Language::where('language', config('app.locale'))->first();
    Translation::factory()->shortKey()->create(['language_id' => $default->id, 'group' => 'test', 'key' => 'hello', 'value' => 'Hello']);
    $this->assertDatabaseHas('translations', ['language_id' => $default->id, 'group' => 'test', 'key' => 'hello', 'value' => 'Hello']);

    $this->post('languages/en', ['group' => 'test', 'key' => 'hello', 'value' => 'Hello there!'])
        ->assertStatus(200);

    $this->assertDatabaseHas('translations', ['language_id' => $default->id, 'group' => 'test', 'key' => 'hello', 'value' => 'Hello there!']);
});

it('fires an event when a translation is added', function () {
    Event::fake();

    $data = ['key' => 'joe', 'value' => 'is cool'];
    $this->post('languages/en/translations', $data);

    Event::assertDispatched(TranslationAdded::class, function ($event) use ($data) {
        return $event->language === 'en' &&
                $event->group === null &&
                $event->value === $data['value'] &&
                $event->key === $data['key'];
    });
});

it('fires an event when a translation is updated', function () {
    Event::fake();

    $data = ['group' => 'test', 'key' => 'hello', 'value' => 'Hello there!'];
    $this->post('languages/en/translations', $data);

    Event::assertDispatched(TranslationAdded::class, function ($event) use ($data) {
        return $event->language === 'en' &&
                $event->group === $data['group'] &&
                $event->value === $data['value'] &&
                $event->key === $data['key'];
    });
});
