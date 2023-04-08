<?php

namespace JoeDixon\Translation\Http\Controllers\Api;

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
        if ($request->has('language') && $request->get('language') !== $language) {
            return redirect()
              ->route('languages.translations.index', [
                  'language' => $request->get('language'), 'group' => $request->get('group'),
                  'filter' => $request->get('filter'),
              ]);
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

        return response()->json([
            'success' => true,
            'message' => 'Translations fetched successfully',
            'data' => [
                'languages' => $languages,
                'groups' => $groups,
                'translations' => $translations,
            ],
        ]);
    }

    public function store(TranslationRequest $request, $language)
    {
        $isGroupTranslation = $request->filled('group');

        $this->translation->add($request, $language, $isGroupTranslation);

        return response()->json([
            'success' => true,
            'message' => 'Translation added successfully',
        ]);
    }

    public function update(Request $request, $language)
    {
        $isGroupTranslation = ! Str::contains($request->get('group'), 'single');

        $this->translation->add($request, $language, $isGroupTranslation);

        return response()->json([
            'success' => true,
            'message' => 'Translation updated successfully',
        ]);
    }
}
