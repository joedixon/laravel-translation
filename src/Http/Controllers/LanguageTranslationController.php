<?php

namespace JoeDixon\Translation\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use JoeDixon\Translation\Http\Requests\TranslationRequest;
use JoeDixon\TranslationCore\TranslationManager;

class LanguageTranslationController extends Controller
{
    private $translation;

    public function __construct(TranslationManager $translation)
    {
        $this->translation = $translation;
    }

    public function index(Request $request, $language)
    {
        if ($request->has('language') && $request->get('language') !== $language) {
            return redirect()
                ->route('languages.translations.index', ['language' => $request->get('language'), 'group' => $request->get('group'), 'filter' => $request->get('filter')]);
        }

        $languages = $this->translation->languages();
        $groups = $this->translation->shortKeyGroups(config('app.locale'));
        $translations = $this->translation->allTranslationsFor($language);
        // $translations = $this->translation->filterTranslationsFor($language, $request->get('filter'));

        // if ($request->has('group') && $request->get('group')) {
        //     if ($request->get('group') === 'single') {
        //         $translations = $translations->get('single');
        //         $translations = new Collection(['single' => $translations]);
        //     } else {
        //         $translations = $translations->get('group')->filter(function ($values, $group) use ($request) {
        //             return $group === $request->get('group');
        //         });

        //         $translations = new Collection(['group' => $translations]);
        //     }
        // }

        return view('translation::languages.translations.index', compact('language', 'languages', 'groups', 'translations'));
    }

    public function create(Request $request, $language)
    {
        return view('translation::languages.translations.create', compact('language'));
    }

    public function store(TranslationRequest $request, $language)
    {
        $this->translation->add(
            $language,
            $request->get('key'),
            $request->get('value'),
            $request->get('group'),
            $request->get('vendor')
        );

        return redirect()
            ->route('languages.translations.index', $language)
            ->with('success', __('translation::translation.translation_added'));
    }

    public function update(Request $request, $language)
    {
        $this->translation->add(
            $language,
            $request->get('key'),
            $request->get('value'),
            $request->get('group'),
            $request->get('vendor')
        );

        return ['success' => true];
    }
}
