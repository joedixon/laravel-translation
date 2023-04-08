<?php

Route::get('/', 'LanguageController@index')->name('languages.index');

Route::get('/create', 'LanguageController@create')->name('languages.create');

Route::post('/', 'LanguageController@store')->name('languages.store');

Route::prefix('/{language}')->group(function () {
    Route::get('/translations', 'LanguageTranslationController@index')->name('languages.translations.index');

    Route::post('/', 'LanguageTranslationController@update')->name('languages.translations.update');

    Route::get('/translations/create', 'LanguageTranslationController@create')->name('languages.translations.create');

    Route::post('/translations', 'LanguageTranslationController@store')->name('languages.translations.store');
});
