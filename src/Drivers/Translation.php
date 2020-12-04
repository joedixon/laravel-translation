<?php

namespace JoeDixon\Translation\Drivers;

use Exception;
use Illuminate\Support\Str;
use Yandex\Translate\Translator;
use Illuminate\Support\Collection;
use Aws\Translate\TranslateClient; 
use Aws\Exception\AwsException;

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

        $client = new TranslateClient([
            'profile' => 'default',
            'region' => 'eu-central-1',
            'version' => '2017-07-01'
        ]);

        

        foreach ($languages as $language => $name) {
            $missingTranslations = $this->findMissingTranslations($language);
            $defaultTranslations = $this->allTranslationsFor(config('app.locale'));

            foreach ($missingTranslations as $type => $groups) {
                foreach ($groups as $group => $translations) {
                    foreach ($translations as $key => $value) {
             
                        if (config('app.locale') == $language && config('translation.copy_key_to_value_if_default_locale') == true) {
                            /**
                             * Missing in default locale
                             */
                            if ($type == 'single') {
                                /**
                                 * Single copy $key to $value
                                 */
                                $this->addSingleTranslation($language, $group, $key, $key);
                            }else{
                                /**
                                 * Group add empty translation
                                 */
                                $this->addGroupTranslation($language, $group, $key);
                            }


                        } else {
                            /**
                             * Missing in any other locale
                             */
                            $newValue = $defaultTranslations->get($type)->get($group)->get($key);
                            //dd($newValue);
                            
                            try {
                                $result = $client->translateText([
                                    'SourceLanguageCode' => config('app.locale'),
                                    'TargetLanguageCode' => $language, 
                                    'Text' => $newValue, 
                                ]);
                                $newValue = config('translation.services.aws.prefix-new') . (string) $result->get('TranslatedText');
            
                            } catch (\Exception $e) {
                                $newValue = config('translation.services.aws.prefix-error') . $e->getMessage() . $newValue;
                            }
                            if ($type == 'single') {
                                /**
                                 * Add single translation
                                 */
                                $this->addSingleTranslation($language, $group, $key, $newValue);
                            }else{
                                /**
                                 * Add Group Translation
                                 */
                                $this->addGroupTranslation($language, $group, $key, $newValue);
                            }
                        } 
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
        $sourceTranslations = $this->allTranslationsFor($this->sourceLanguage);
        $languageTranslations = $this->allTranslationsFor($language);

        return $sourceTranslations->map(function ($groups, $type) use ($language, $languageTranslations) {
            return $groups->map(function ($translations, $group) use ($type, $language, $languageTranslations) {
                $translations = $translations->toArray();
                array_walk($translations, function (&$value, &$key) use ($type, $group, $language, $languageTranslations) {
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
     *
     * @param string $language
     * @param string $filter
     * @return Collection
     */
    public function filterTranslationsFor($language, $filter)
    {
        $allTranslations = $this->getSourceLanguageTranslationsWith(($language));
        if (! $filter) {
            return $allTranslations;
        }

        return $allTranslations->map(function ($groups, $type) use ($language, $filter) {
            return $groups->map(function ($keys, $group) use ($language, $filter, $type) {
                return collect($keys)->filter(function ($translations, $key) use ($group, $language, $filter, $type) {
                    return strs_contain([$group, $key, $translations[$language], $translations[$this->sourceLanguage]], $filter);
                });
            })->filter(function ($keys) {
                return $keys->isNotEmpty();
            });
        });
    }
}
