<?php

Route::group(config('translation.route_group_config') + ['namespace' => 'JoeDixon\\Translation\\Http\\Controllers'], function ($router) {
    $router->get(config('translation.ui_url'), [config('translation.controllers.language'), 'index'])
        ->name('languages.index');

    $router->get(config('translation.ui_url').'/create', [config('translation.controllers.language'), 'create'])
        ->name('languages.create');

    $router->post(config('translation.ui_url'), [config('translation.controllers.language'), 'store'])
        ->name('languages.store');

    $router->get(config('translation.ui_url').'/{language}/translations', [config('translation.controllers.languageTranslation'), 'index'])
        ->name('languages.translations.index');

    $router->post(config('translation.ui_url').'/{language}', [config('translation.controllers.languageTranslation'), 'update'])
        ->name('languages.translations.update');

    $router->get(config('translation.ui_url').'/{language}/translations/create', [config('translation.controllers.languageTranslation'), 'create'])
        ->name('languages.translations.create');

    $router->post(config('translation.ui_url').'/{language}/translations', [config('translation.controllers.languageTranslation'), 'store'])
        ->name('languages.translations.store');
});
