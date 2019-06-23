<?php

Route::group(config('translation.route_group_config') + ['namespace' => 'JoeDixon\\Translation\\Http\\Controllers'], function ($router) {
    $router->get(config('translation.ui_url'), 'LanguageController@index')
        ->name('languages.index');

    $router->get(config('translation.ui_url').'/create', 'LanguageController@create')
        ->name('languages.create');

    $router->post(config('translation.ui_url'), 'LanguageController@store')
        ->name('languages.store');

    $router->get(config('translation.ui_url').'/{language}/translations', 'LanguageTranslationController@index')
        ->name('languages.translations.index');

    $router->post(config('translation.ui_url').'/{language}', 'LanguageTranslationController@update')
        ->name('languages.translations.update');

    $router->get(config('translation.ui_url').'/{language}/translations/create', 'LanguageTranslationController@create')
        ->name('languages.translations.create');

    $router->post(config('translation.ui_url').'/{language}/translations', 'LanguageTranslationController@store')
        ->name('languages.translations.store');
});
