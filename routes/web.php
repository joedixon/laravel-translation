<?php

Route::group(config('translation.route_group_config'), function ($router) {
    $router->get(config('translation.ui_url'), 'LanguageController@index')
        ->name('languages.index');

    $router->get(config('translation.ui_url') . '/create', 'LanguageController@create')
        ->name('languages.create');

    $router->post(config('translation.ui_url'), 'LanguageController@store')
        ->name('languages.store');
});
