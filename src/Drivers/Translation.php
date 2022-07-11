<?php

namespace JoeDixon\Translation\Drivers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use JoeDixon\Translation\Events\TranslationAdded;

abstract class Translation
{
    /**
     * Get all languages.
     */
    public abstract function allLanguages(): Collection;

    /**
     * Get all translations.
     */
    public abstract function allTranslations(): Collection;

    /**
     * Get all group translations for a given language.
     */
    public abstract function allGroup(string $language): Collection;

    /**
     * Get all translations for a given language.
     */
    public abstract function allTranslationsFor(string $language): Collection;

    /**
     * Add a new language.
     */
    public abstract function addLanguage(string $language, ?string $name = null): void;

    /**
     * Add a group translation.
     */
    public abstract function addGroupTranslation(string $language, string $group, string $key, string $value = ''): void;

    /**
     * Add a single translation.
     */
    public abstract function addSingleTranslation(string $language, string $vendor, string $key, string $value = ''): void;

    /**
     * Get single translations for a given language.
     */
    public abstract function getSingleTranslationsFor(string $language): Collection;

    /**
     * Get group translations for a given language.
     */
    public abstract function getGroupTranslationsFor(string $language): Collection;

    /**
     * Determine whether the given language exists.
     */
    public abstract function languageExists(string $language): bool;

    /**
     * Get all the groups for a given language.
     */
    public abstract function getGroupsFor(string $language): Collection;

    /**
     * Find all of the translations in the app without translation for a given language.
     */
    public function findMissingTranslations(string $language): Collection
    {
        return new Collection(
            array_diff_assoc_recursive(
                $this->scanner->findTranslations(),
                $this->allTranslationsFor($language)
            )
        );
    }

    /**
     * Save all of the translations in the app without translation for a given language.
     */
    public function saveMissingTranslations(string $language = '')
    {
        $languages = $language ? [$language => $language] : $this->allLanguages();

        foreach ($languages as $language => $name) {
            $missingTranslations = $this->findMissingTranslations($language);

            foreach ($missingTranslations as $type => $groups) {
                foreach ($groups as $group => $translations) {
                    foreach ($translations as $key => $value) {
                        if (Str::contains($group, 'single')) {
                            $this->addSingleTranslation($language, $group, $key);
                        } else {
                            $this->addGroupTranslation($language, $group, $key);
                        }
                    }
                }
            }
        }
    }

    /**
     * Get all translations for a given language merged with the source language.
     */
    public function getSourceLanguageTranslationsWith(string $language): Collection
    {
        $sourceTranslations = $this->allTranslationsFor($this->sourceLanguage);
        $languageTranslations = $this->allTranslationsFor($language);

        return $sourceTranslations->map(function ($groups, $type) use ($language, $languageTranslations) {
            return $groups->map(function ($translations, $group) use ($type, $language, $languageTranslations) {
                $translations = $translations->toArray();
                array_walk($translations, function (&$value, $key) use ($type, $group, $language, $languageTranslations) {
                    $value = [
                        $this->sourceLanguage => $value,
                        $language => $languageTranslations->get($type, collect())->get($group, collect())->get($key),
                    ];
                });

                return $translations;
            });
        });
    }

    /**
     * Filter all keys and translations for a given language and string.
     */
    public function filterTranslationsFor(string $language, ?string $filter): Collection
    {
        $allTranslations = $this->getSourceLanguageTranslationsWith(($language));
        if (!$filter) {
            return $allTranslations;
        }

        return $allTranslations->map(function ($groups, $type) use ($language, $filter) {
            return $groups->map(function ($keys, $group) use ($language, $filter) {
                return collect($keys)->filter(function ($translations, $key) use ($group, $language, $filter) {
                    return strs_contain([$group, $key, $translations[$language], $translations[$this->sourceLanguage]], $filter);
                });
            })->filter(function ($keys) {
                return $keys->isNotEmpty();
            });
        });
    }

    public function add(Request $request, $language, $isGroupTranslation)
    {
        $namespace = $request->has('namespace') && $request->get('namespace') ? "{$request->get('namespace')}::" : '';
        $group = $namespace . $request->get('group');
        $key = $request->get('key');
        $value = $request->get('value') ?: '';

        if ($isGroupTranslation) {
            $this->addGroupTranslation($language, $group, $key, $value);
        } else {
            $this->addSingleTranslation($language, 'single', $key, $value);
        }

        Event::dispatch(new TranslationAdded($language, $group ?: 'single', $key, $value));
    }
}
