<?php

use JoeDixon\Translation\Drivers\CombinedTranslations;
use JoeDixon\Translation\Drivers\Translation;
use JoeDixon\Translation\Exceptions\LanguageExistsException;
use JoeDixon\Translation\Tests\TestCase;
use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertEquals;

uses(TestCase::class)->in(__DIR__);

beforeEach(function () {
    $this->translation = app(Translation::class);
});

test('a map of translation files can be built', function () {
    assertEquals('vendor/laravel-translation/en/laravel-translation.php', $this->translation->map()->last());
});

test('a translation file can be found from the translation file map', function () {
    assertEquals(
        'en.json',
        $this->translation->map('en')
    );
});

test('all languages can be returned', function () {
    assertEquals(
        collect([
            'de' => 'de',
            'en' => 'en',
            'es' => 'es',
            'fr' => 'fr',
            'jp' => 'jp',
        ]),
        $this->translation->allLanguages()
    );
});

test('a language cannot be overwritten', function () {
    $this->translation->addLanguage('en');
})->throws(LanguageExistsException::class);

test('all translations can be returned from a translation file', function () {
    assertEquals(
        collect([
            'products' => [
                'product_one' => [
                    'title' => 'Product 1',
                    'description' => 'This is product one',
                ],
            ],
        ]),
        $this->translation->allTranslationsFromMap('en.products')
    );
});

test('a full list of normalized translation keys can be found by checking all languages', function () {
    assertEquals(
        new CombinedTranslations(
            collect(['string' => ['Hello' => '', "What's up" => '']]),
            collect([
                'errors' => [],
                'validation' => [
                    'filled' => '',
                    'gt' => [
                        'array' => '',
                        'file' => '',
                        'numeric' => '',
                        'string' => '',
                    ],
                    'before_or_equal' => '',
                    'between' => [
                        'array' => '',
                        'file' => '',
                        'numeric' => '',
                        'string' => '',
                    ],
                ],
                'empty' => [],
                'products' => [
                    'products' => [
                        'product_one' => [
                            'title' => '',
                            'description' => '',
                        ],
                    ],
                ],
                'laravel-translation::laravel-translation' => [
                    'key' => '',
                ],
            ])),
        $this->translation->normalizedKeys()
    );
});

test('an individual translation can be found for a given language', function () {
    assertEquals(
        'Product 1',
        trans('products.products.product_one.title')
    );
});

test('all vendor translations can be returned', function () {
    $translations = $this->translation->allTranslationsFor('en');

    assertCount(1, $translations->shortKeyTranslations['laravel-translation::laravel-translation']);
    assertCount(0, $translations->shortKeyTranslations['laravel-translation::validation']);
    assertCount(1, $translations->stringKeyTranslations['laravel-translation::string']);
});

test('both string key and short key translations can be returned', function () {
    //
})->skip();

test('a string key translation can be saved in the correct file if it exists', function () {
    //
})->skip();

test('a short key translation can be saved in the correct file if it exists', function () {
    //
})->skip();

test('a string key translation can be saved in new file at the highest nesting level if a file cannot be found', function () {
    //
})->skip();

test('a short key translation can be saved in new file at the highest nesting level if a file cannot be found', function () {
    //
})->skip();

test('a vendor namespaced string key translation can be saved', function () {
    //
})->skip();

test('a vendor namespaced short key translation can be saved', function () {
    //
})->skip();
