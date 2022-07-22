<?php

namespace JoeDixon\Translation\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use JoeDixon\Translation\Drivers\Translation;
use JoeDixon\Translation\Http\Requests\LanguageRequest;

class LanguageController extends Controller
{
    public function __construct(
        private Translation $translation
    ) {
    }

    public function index(Request $request): View
    {
        $languages = $this->translation->allLanguages();

        return view('translation::languages.index', compact('languages'));
    }

    public function create(): View
    {
        return view('translation::languages.create');
    }

    public function store(LanguageRequest $request): RedirectResponse
    {
        $this->translation->addLanguage($request->locale, $request->name);

        return redirect()
            ->route('languages.index')
            ->with('success', __('translation::translation.language_added'));
    }
}
