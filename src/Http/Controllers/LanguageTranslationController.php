<?php

namespace JoeDixon\Translation\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use JoeDixon\Translation\Drivers\Translation;
use JoeDixon\Translation\Http\Requests\TranslationRequest;

class LanguageTranslationController extends Controller
{
    private $translation;

    public function __construct(Translation $translation)
    {
        $this->translation = $translation;
    }

    public function index(Request $request, $language)
    {
        // dd($this->translation->getSingleTranslationsFor('en'));
        if ($request->has('language') && $request->get('language') !== $language) {
            return redirect()
                ->route('languages.translations.index', ['language' => $request->get('language'), 'group' => $request->get('group'), 'filter' => $request->get('filter')]);
        }

        $languages = $this->translation->allLanguages();
        $groups = $this->translation->getGroupsFor(config('app.locale'))->merge('single');
        $translations = $this->translation->filterTranslationsFor($language, $request->get('filter'));

        if ($request->has('group') && $request->get('group')) {
            if ($request->get('group') === 'single') {
                $translations = $translations->get('single');
                $translations = new Collection(['single' => $translations]);
            } else {
                $translations = $translations->get('group')->filter(function ($values, $group) use ($request) {
                    return $group === $request->get('group');
                });

                $translations = new Collection(['group' => $translations]);
            }
        }

        return view('translation::languages.translations.index', compact('language', 'languages', 'groups', 'translations'));
    }

    public function create(Request $request, $language)
    {
        return view('translation::languages.translations.create', compact('language'));
    }

    public function store(TranslationRequest $request, $language)
    {
        if ($request->filled('group')) {
            $namespace = $request->has('namespace') && $request->get('namespace') ? "{$request->get('namespace')}::" : '';
            $this->translation->addGroupTranslation($language, "{$namespace}{$request->get('group')}", $request->get('key'), $request->get('value') ?: '');
        } else {
            $this->translation->addSingleTranslation($language, 'single', $request->get('key'), $request->get('value') ?: '');
        }

        return redirect()
            ->route('languages.translations.index', $language)
            ->with('success', __('translation::translation.translation_added'));
    }

    public function update(Request $request, $language)
    {
        if (! Str::contains($request->get('group'), 'single')) {
            $this->translation->addGroupTranslation($language, $request->get('group'), $request->get('key'), $request->get('value') ?: '');
        } else {
            $this->translation->addSingleTranslation($language, $request->get('group'), $request->get('key'), $request->get('value') ?: '');
        }

        return ['success' => true];
    }
}
