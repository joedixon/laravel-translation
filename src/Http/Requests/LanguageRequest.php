<?php

namespace JoeDixon\Translation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use JoeDixon\Translation\Rules\LanguageNotExists;

class LanguageRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name'   => 'nullable|string',
            'locale' => ['required', new LanguageNotExists()],
        ];
    }
}
