<?php

use Illuminate\Support\Facades\Route;
use JoeDixon\Translation\Livewire\Translations;

Route::group(config('translation.route_group_config'), function () {
    Route::get(config('translation.ui_url'), Translations::class)
        ->name('translations.index');
});
