<?php

namespace JoeDixon\Translation\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class TranslationController extends Controller
{
    private $translation;

    public function __construct()
    {
        $this->translation = app()->make('translation');
    }

    public function index(Request $request)
    {
        $baseLang = config('app.locale');
        $translations = [];
        $languages = $this->translation->allLanguages();
        $language = $request->get('language') ?: $languages[0];
        $groups = $this->translation->allGroups(config('app.locale'));

        if ($request->has('file')) {
            $file = $request->get('file');
            $translations = $this->translation->getTranslationsForFile($baseLang, $file);
        } else {
            $translations = $this->translation->allTranslationsFor($baseLang);
            if (isset($translations['json'])) {
                $file = 'json';
                $translations = $translations['json'];
            } elseif (isset($translations['array'])) {
                $file = array_keys($translations['array'])[0];
                $translations = $this->translation->getTranslationsForFile($baseLang, $file);
            }
        }

        $translations = $this->translation->merge($translations, $language);

        return view('translation::languages.index', compact('languages', 'language', 'translations', 'groups', 'file'));
    }
}
