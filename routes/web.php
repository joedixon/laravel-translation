<?php

Route::group(config('translation.route_group_config'), function ($router) {
    Route::get(config('translation.ui_url'), 'TranslationController@index')->name('languages.index');
});
