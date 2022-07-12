<?php

namespace JoeDixon\Translation\Drivers\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use JoeDixon\Translation\Drivers\Translation;
use JoeDixon\Translation\Exceptions\LanguageExistsException;
use JoeDixon\Translation\Language;

class Database extends Translation
{
    use InteractsWithStringKeys, InteractsWithShortKeys;

    public function __construct(protected $sourceLanguage, protected $scanner)
    {
    }

    /**
     * Get all languages.
     */
    public function allLanguages(): Collection
    {
        return Language::all()->mapWithKeys(function ($language) {
            return [$language->language => $language->name ?: $language->language];
        });
    }

    /**
     * Determine whether the given language exists.
     */
    public function languageExists(string $language): bool
    {
        return $this->getLanguage($language) ? true : false;
    }

    /**
     * Add a new language.
     */
    public function addLanguage(string $language, ?string $name = null): void
    {
        if ($this->languageExists($language)) {
            throw new LanguageExistsException(__('translation::errors.language_exists', ['language' => $language]));
        }

        Language::create([
            'language' => $language,
            'name' => $name,
        ]);
    }

    /**
     * Get all translations.
     */
    public function allTranslations(): Collection
    {
        return $this->allLanguages()->mapWithKeys(function ($name, $language) {
            return [$language => $this->allTranslationsFor($language)];
        });
    }

    /**
     * Get all translations for a given language.
     */
    public function allTranslationsFor(string $language): Collection
    {
        return Collection::make([
            'short' => $this->allShortKeyTranslationsFor($language),
            'string' => $this->allStringKeyTranslationsFor($language),
        ]);
    }

    /**
     * Get a language from the database.
     */
    private function getLanguage(string $language): ?Model
    {
        return Language::where('language', $language)->first();
    }
}
