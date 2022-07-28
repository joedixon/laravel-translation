<?php

namespace JoeDixon\Translation\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use JoeDixon\Translation\Drivers\Translation;
use JoeDixon\Translation\Http\Requests\TranslationRequest;

class LanguageTranslationController extends Controller
{
    public function __construct(
        private Translation $translation
    ) {
    }

    public function index(Request $request, string $language): RedirectResponse|View
    {
        if ($request->has('language') && $request->get('language') !== $language) {
            return redirect()
                ->route('languages.translations.index', [
                    'language' => $request->get('language'),
                    'group' => $request->get('group'),
                    'filter' => $request->get('filter'),
                ]);
        }

        $languages = $this->translation->allLanguages();
        $groups = $this->translation->allShortKeyGroupsFor(config('app.locale'))->merge('single');
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

    public function create(Request $request, string $language): View
    {
        return view('translation::languages.translations.create', compact('language'));
    }

    public function store(TranslationRequest $request, string $language): RedirectResponse
    {
        $isGroupTranslation = $request->filled('group');

        $this->translation->add($request, $language, $isGroupTranslation);

        return redirect()
            ->route('languages.translations.index', $language)
            ->with('success', __('translation::translation.translation_added'));
    }

    public function update(Request $request, string $language): JsonResponse
    {
        $isGroupTranslation = ! Str::contains($request->get('group'), 'single');

        $this->translation->add($request, $language, $isGroupTranslation);

        return new JsonResponse(['success' => true]);
    }
}
