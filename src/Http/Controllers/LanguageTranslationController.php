<?php

namespace JoeDixon\Translation\Http\Controllers;

use Illuminate\Routing\Controller;

class LanguageTranslationController extends Controller
{
    private $translation;

    public function __construct()
    {
        $this->translation = app()->make('translation');
    }

    public function index($language)
    {
        $languages = $this->translation->allLanguages();
        $groups = $this->translation->getGroupsFor(config('app.locale'));
        $translations = $this->translation->getBaseTranslationsWith($language);

        return view('translation::languages.translations.index', compact('language', 'languages', 'groups', 'translations'));
    }
}
