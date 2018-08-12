<?php

namespace JoeDixon\Translation\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Routing\Controller;

class LanguageTranslationController extends Controller
{
    private $translation;

    public function __construct()
    {
        $this->translation = app()->make('translation');
    }

    public function index(Request $request, $language)
    {
        if ($request->has('language')) {
            return redirect()
                ->route('languages.translations.index', ['language' => $request->get('language'), 'group' => $request->get('group')]);
        }

        $languages = $this->translation->allLanguages();
        $groups = $this->translation->getGroupsFor(config('app.locale'));

        $translations = $this->translation->getBaseTranslationsWith($language);

        if ($request->has('group')) {
            $translations = $translations->get('group')->filter(function ($values, $group) use ($request) {
                return $group === $request->get('group');
            });

            $translations = new Collection(['group' => $translations]);
        }

        // dd($translations);

        return view('translation::languages.translations.index', compact('language', 'languages', 'groups', 'translations'));
    }
}
