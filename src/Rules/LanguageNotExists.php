<?php

namespace JoeDixon\Translation\Rules;

use Illuminate\Contracts\Validation\Rule;
use JoeDixon\Translation\Drivers\Translation;

class LanguageNotExists implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $translation = app()->make(Translation::class);

        return ! $translation->languageExists($value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('translation::translation.language_exists');
    }
}
