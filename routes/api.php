<?php

Route::get('/', 'LanguageController@index');

Route::post('/', 'LanguageController@store');

Route::get('/{language}/translations', 'LanguageTranslationController@index');

Route::post('/{language}', 'LanguageTranslationController@update');

Route::post('/{language}/translations', 'LanguageTranslationController@store');
