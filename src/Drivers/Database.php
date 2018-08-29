<?php

namespace JoeDixon\Translation\Drivers;

use JoeDixon\Translation\Language;
use JoeDixon\Translation\Exceptions\LanguageExistsException;
use JoeDixon\Translation\Translation;

class Database extends Translation implements DriverInterface
{
    private $sourceLanguage;

    public function __construct($sourceLanguage)
    {
        $this->sourceLanguage = $sourceLanguage;
    }

    /**
     * Get all languages from the application
     *
     * @return Collection
     */
    public function allLanguages()
    {
        return Language::all();
    }

    /**
     * Get all group translations from the application
     *
     * @return array
     */
    public function allGroup($language)
    {
        return Translation::getGroupsForLanguage($language);
    }

    /**
     * Get all the translations from the application
     *
     * @return Collection
     */
    public function allTranslations()
    {
    }

    /**
     * Get all translations for a particular language
     *
     * @param string $language
     * @return Collection
     */
    public function allTranslationsFor($language)
    {
    }

    /**
     * Add a new language to the application
     *
     * @param string $language
     * @return void
     */
    public function addLanguage($language, $name = null)
    {
        if ($this->languageExists($language)) {
            throw new LanguageExistsException(__('translation::errors.language_exists', ['language' => $language]));
        }

        Language::create([
            'language' => $language,
            'name' => $name
        ]);
    }

    /**
     * Add a new group type translation
     *
     * @param string $language
     * @param string $key
     * @param string $value
     * @return void
     */
    public function addGroupTranslation($language, $key, $value = '')
    {
        if (!$this->languageExists($language)) {
            $this->addLanguage($language);
        }

        list($group, $key) = explode('.', $key);

        $translation = new Translation([
            'group' => $group,
            'key' => $key,
            'value' => $value
        ]);

        Language::where('language', $language)
            ->first()
            ->translations()
            ->save($translation);
    }

    /**
     * Add a new single type translation
     *
     * @param string $language
     * @param string $key
     * @param string $value
     * @return void
     */
    public function addSingleTranslation($language, $key, $value = '')
    {
    }

    /**
     * Get all of the single translations for a given language
     *
     * @param string $language
     * @return Collection
     */
    public function getSingleTranslationsFor($language)
    {
    }

    /**
     * Get all of the group translations for a given language
     *
     * @param string $language
     * @return Collection
     */
    public function getGroupTranslationsFor($language)
    {
        $translations = $this->getLanguage($language)
            ->translations()
            ->whereNotNull('group')
            ->get()
            ->groupBy('group');

        return  $translations->map(function ($translations) {
            return $translations->mapWithKeys(function ($translation) {
                return [$translation->key => $translation->value];
            });
        });
    }

    /**
     * Determine whether or not a language exists
     *
     * @param string $language
     * @return boolean
     */
    public function languageExists($language)
    {
        return $this->getLanguage($language) ? true : false;
    }

    /**
     * Add a new group of translations
     *
     * @param string $language
     * @param string $group
     * @return void
     */
    public function addGroup($language, $group)
    {
    }

    /**
     * Find all of the translations in the app without translation for a given language
     *
     * @param string $language
     * @return array
     */
    public function findMissingTranslations($language)
    {
    }

    /**
     * Save all of the translations in the app without translation for a given language
     *
     * @param string $language
     * @return void
     */
    public function saveMissingTranslations($language = false)
    {
    }

    /**
     * Get a collection of group names for a given language
     *
     * @param string $language
     * @return Collection
     */
    public function getGroupsFor($language)
    {
    }

    /**
     * Get all translations for a given language merged with the source language
     *
     * @param string $language
     * @return Collection
     */
    public function getSourceLanguageTranslationsWith($language)
    {
    }

    /**
     * Filter all keys and translations for a given language and string
     *
     * @param string $language
     * @param string $filter
     * @return Collection
     */
    public function filterTranslationsFor($language, $filter)
    {
    }

    private function getLanguage($language)
    {
        return Language::where('language', $language)->first();
    }
}
