<?php

namespace JoeDixon\Translation\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use JoeDixon\Translation\Drivers\Translation;
use JoeDixon\Translation\Http\Requests\LanguageRequest;

class LanguageController extends Controller
{
    private $translation;

    public function __construct(Translation $translation)
    {
        $this->translation = $translation;
    }

    public function index(Request $request)
    {
        return response()->json([
          'success' => true,
          'message' => 'Languages fetched successfully',
          'data' => $this->translation->allLanguages(),
        ]);
    }

    public function store(LanguageRequest $request)
    {
        $this->translation->addLanguage($request->locale, $request->name);

        return response()->json([
          'success' => true,
          'message' => 'Languages added successfully',
          'data' => $this->translation->allLanguages(),
        ]);
    }
}
