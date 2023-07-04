<?php

namespace JoeDixon\Translation\Tests;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use JoeDixon\TranslationCore\Events\TranslationAdded;
use JoeDixon\TranslationCore\Translation;
use Tests\Cases\FileProviderTestCase;

uses(FileProviderTestCase::class);

beforeEach(function () {
    File::deleteDirectory($this->app->langPath());
    File::copyDirectory(__DIR__.'/fixtures/lang', $this->app->langPath());
    $this->translation = $this->app->make(Translation::class);
});

afterEach(function () {
    File::deleteDirectory($this->app->langPath());
});

it('can list all languages', function () {
    $this->get(config('translation.ui_url'))
        ->assertSee('en');
});

it('can render the language creation page', function () {
    $this->translation->addShortKeyTranslation(config('app.locale'), 'translation', 'add_language', 'Add a new language', 'translation');

    $this->get(config('translation.ui_url').'/create')
        ->assertSee('Add a new language');
});

it('can add a new language', function () {
    $this->post(config('translation.ui_url'), ['locale' => 'fr'])
        ->assertRedirect();

    $this->assertTrue(file_exists(__DIR__.'/lang/fr.json'));
    $this->assertTrue(file_exists(__DIR__.'/lang/fr'));
});

it('can list all translations', function () {
    $this->get(config('translation.ui_url').'/en/translations')
        ->assertSee('hello')
        ->assertSee('whats_up');
});

it('can render the translation creation page', function () {
    $this->translation->addShortKeyTranslation('en', 'translation', 'add_translation', 'Add a translation', 'translation');

    $this->get(config('translation.ui_url').'/'.config('app.locale').'/translations/create')
        ->assertSee('Add a translation');
});

it('can add a new translation', function () {
    $this->post(config('translation.ui_url').'/en/translations', ['key' => 'joe', 'value' => 'is cool'])
        ->assertRedirect();

    $translations = $this->translation->stringKeyTranslations('en');

    $this->assertEquals(
        ['Hello' => 'Hello', 'What\'s up' => 'What\'s up!', 'joe' => 'is cool', 'laravel-translation' => ['key' => 'value']],
        $translations->toArray()
    );
});

it('can update a translation', function () {
    $this->post(config('translation.ui_url').'/en', ['group' => 'home', 'key' => 'title', 'value' => 'Home!'])
        ->assertStatus(200);

    $translations = $this->translation->shortKeyTranslations('en');

    $this->assertEquals(['title' => 'Home!'], $translations->toArray()['home']);
});

it('fires an event when a translation is added', function () {
    Event::fake();

    $data = ['key' => 'joe', 'value' => 'is cool'];

    $this->post(config('translation.ui_url').'/en/translations', $data);

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

    $this->post(config('translation.ui_url').'/en/translations', $data);

    Event::assertDispatched(TranslationAdded::class, function ($event) use ($data) {
        return $event->language === 'en' &&
            $event->group === $data['group'] &&
            $event->value === $data['value'] &&
            $event->key === $data['key'];
    });
});
