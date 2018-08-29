<?php

use JoeDixon\Translation\Http\Controllers\LanguageTranslationController;
use JoeDixon\Translation\Drivers\Translation;

Route::group(config('translation.route_group_config'), function ($router) {
    $router->get(config('translation.ui_url'), 'LanguageController@index')
        ->name('languages.index');

    $router->get(config('translation.ui_url') . '/create', 'LanguageController@create')
        ->name('languages.create');

    $router->post(config('translation.ui_url'), 'LanguageController@store')
        ->name('languages.store');

    $router->get(config('translation.ui_url') . '/{language}/translations', 'LanguageTranslationController@index')
        ->name('languages.translations.index');

    $router->post(config('translation.ui_url') . '/{language}', 'LanguageTranslationController@update')
        ->name('languages.translations.update');

    $router->get('test', function () {
        dd(app()->make(Translation::class)->getGroupTranslationsFor('en'));
    });
});
