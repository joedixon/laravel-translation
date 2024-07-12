<?php

namespace JoeDixon\Translation\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use JoeDixon\Translation\Drivers\Translation;
use JoeDixon\Translation\Http\Requests\LanguageRequest;
use JoeDixon\Translation\Http\Requests\LanguageDestroyRequest;

class LanguageController extends Controller
{
    private $translation;

    public function __construct(Translation $translation)
    {
        $this->translation = $translation;
    }

    public function index(Request $request)
    {
        $languages = $this->translation->allLanguages();

        return inertia('Control/Languages/Index', compact('languages'));
    }

    public function create()
    {
        return inertia('Control/Languages/Create');
    }

    public function store(LanguageRequest $request)
    {
        $this->translation->addLanguage($request->validated()['locale'], $request->validated()['name']);

        return redirect()
            ->route('languages.index')
            ->with('success', __('translation::translation.language_added'));
    }

    public function destroy(LanguageDestroyRequest $request)
    {

        $this->translation->deleteDirectory($request->validated()['language']);

        return redirect()->back();
    }
}