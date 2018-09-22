<?php

namespace JoeDixon\Translation\Drivers;

use Illuminate\Support\Collection;

abstract class Translation
{
    /**
     * Find all of the translations in the app without translation for a given language.
     *
     * @param string $language
     * @return array
     */
    public function findMissingTranslations($language)
    {
        return array_diff_assoc_recursive(
            $this->scanner->findTranslations(),
            $this->allTranslationsFor($language)
        );
    }

    /**
     * Save all of the translations in the app without translation for a given language.
     *
     * @param string $language
     * @return void
     */
    public function saveMissingTranslations($language = false)
    {
        $languages = $language ? [$language => $language] : $this->allLanguages();

        foreach ($languages as $language => $name) {
            $missingTranslations = $this->findMissingTranslations($language);
            if (isset($missingTranslations['single'])) {
                foreach ($missingTranslations['single'] as $key => $value) {
                    $this->addSingleTranslation($language, $key);
                }
            }

            if (isset($missingTranslations['group'])) {
                foreach ($missingTranslations['group'] as $group => $keys) {
                    foreach ($keys as $key => $value) {
                        $this->addGroupTranslation($language, "{$group}.{$key}");
                    }
                }
            }
        }
    }

    /**
     * Get all translations for a given language merged with the source language.
     *
     * @param string $language
     * @return Collection
     */
    public function getSourceLanguageTranslationsWith($language)
    {
        $mergedTranslations = new Collection;

        // Group translations
        $sourceGroupTranslations = $this->getGroupTranslationsFor($this->sourceLanguage);
        $languageGroupTranslations = $this->getGroupTranslationsFor($language);

        $groupTranslations = $sourceGroupTranslations->map(function ($translations, $group) use ($language, $languageGroupTranslations) {
            array_walk($translations, function (&$value, &$key) use ($group, $language, $languageGroupTranslations) {
                $value = [$this->sourceLanguage => $value, $language => data_get($languageGroupTranslations, "{$group}.{$key}", '')];
            });

            return $translations;
        });

        // Single translations
        $sourceSingleTranslations = $this->getSingleTranslationsFor($this->sourceLanguage);
        $languageSingleTranslations = $this->getSingleTranslationsFor($language);

        $singleTranslations = $sourceSingleTranslations->map(function ($value, $key) use ($language, $languageSingleTranslations) {
            return [$this->sourceLanguage => $value, $language => data_get($languageSingleTranslations, $key, '')];
        });

        return Collection::make([
            'group' => $groupTranslations,
            'single' => $singleTranslations,
        ]);
    }

    /**
     * Filter all keys and translations for a given language and string.
     *
     * @param string $language
     * @param string $filter
     * @return Collection
     */
    public function filterTranslationsFor($language, $filter)
    {
        $filteredTranslations = new Collection;
        $allTranslations = $this->getSourceLanguageTranslationsWith(($language));
        if (! $filter) {
            return $allTranslations;
        }

        // group translations
        $filteredGroupTranslations = new Collection;
        $allTranslations->get('group')->each(function ($groups, $groupKey) use ($language, $filter, $filteredGroupTranslations) {
            foreach ($groups as $key => $translations) {
                if (is_array($translations[$this->sourceLanguage])) {
                    continue;
                }
                if (strs_contain([$key, $translations[$language], $translations[$this->sourceLanguage]], $filter)) {
                    $current = (array) $filteredGroupTranslations->get($groupKey);
                    $current[$key] = $translations;
                    $filteredGroupTranslations->put($groupKey, $current);
                }
            }
        });

        // single translations
        $filteredSingleTranslations = $allTranslations->get('single')->filter(function ($translations, $key) use ($language, $filter) {
            return strs_contain([$key, $translations[$language], $translations[$this->sourceLanguage]], $filter);
        });

        $filteredTranslations->put('group', $filteredGroupTranslations)->put('single', $filteredSingleTranslations);

        return $filteredTranslations;
    }
}
