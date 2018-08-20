<?php

namespace JoeDixon\Translation\Drivers;

use JoeDixon\Translation\Scanner;
use Illuminate\Support\Collection;
use Illuminate\Filesystem\Filesystem;
use JoeDixon\Translation\Exceptions\LanguageExistsException;
use JoeDixon\Translation\Exceptions\LanguageKeyExistsException;

class File implements DriverInterface
{
    private $disk;

    private $languageFilesPath;

    private $sourceLanguage;

    private $scanner;

    public function __construct(Filesystem $disk, $languageFilesPath, $sourceLanguage)
    {
        $this->disk = $disk;
        $this->languageFilesPath = $languageFilesPath;
        $this->sourceLanguage = $sourceLanguage;
        $this->scanner = app()->make(Scanner::class);
    }

    /**
     * Get all languages from the application
     *
     * @return Collection
     */
    public function allLanguages()
    {
        // As per the docs, there should be a subdirectory within the
        // languages path so we can return these directory names as a collection
        $directories = Collection::make($this->disk->directories($this->languageFilesPath));

        return $directories->map(function ($directory) {
            return array_last(explode('/', $directory));
        })->filter(function ($language) {
            // at the moemnt, we're not supporting vendor specific translations
            return $language != 'vendor';
        });
    }

    /**
     * Get all group translations from the application
     *
     * @return array
     */
    public function allGroup($language)
    {
        $groupPath = "{$this->languageFilesPath}/{$language}";

        if (!$this->disk->exists($groupPath)) {
            return [];
        }

        $groups = Collection::make($this->disk->allFiles($groupPath));

        return $groups->map(function ($group) {
            return $group->getBasename('.php');
        });
    }

    /**
     * Get all the translations from the application
     *
     * @return Collection
     */
    public function allTranslations()
    {
        return $this->allLanguages()->mapWithKeys(function ($language) {
            return [$language => $this->allTranslationsFor($language)];
        });
    }

    /**
     * Get all translations for a particular language
     *
     * @param string $language
     * @return Collection
     */
    public function allTranslationsFor($language)
    {
        return Collection::make([
            'group' => $this->getGroupTranslationsFor($language),
            'single' => $this->getSingleTranslationsFor($language)
        ]);
    }

    /**
     * Add a new language to the application
     *
     * @param string $language
     * @return void
     */
    public function addLanguage($language)
    {
        if ($this->languageExists($language)) {
            throw new LanguageExistsException(__('translation::errors.language_exists', ['language' => $language]));
        }

        $this->disk->makeDirectory("{$this->languageFilesPath}/$language");
        if (!$this->disk->exists("{$this->languageFilesPath}/{$language}.json")) {
            $this->saveSingleTranslations($language, []);
        }
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
        $translations = $this->getGroupTranslationsFor($language);

        // does the group exist? If not, create it.
        if (!$translations->keys()->contains($group)) {
            $translations->put($group, []);
        }

        $values = $translations->get($group);
        $values[$key] = $value;
        $translations->put($group, $values);

        $this->saveGroupTranslations($language, $group, $translations->get($group));
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
        if (!$this->languageExists($language)) {
            $this->addLanguage($language);
        }

        $translations = $this->getSingleTranslationsFor($language);

        $translations->put($key, $value);

        $this->saveSingleTranslations($language, $translations);
    }

    /**
     * Get all of the single translations for a given language
     *
     * @param string $language
     * @return Collection
     */
    public function getSingleTranslationsFor($language)
    {
        $singlePath = $this->languageFilesPath . "/$language.json";

        if ($this->disk->exists($singlePath)) {
            return new Collection(json_decode($this->disk->get($singlePath), true));
        }

        return new Collection;
    }

    /**
     * Get all of the group translations for a given language
     *
     * @param string $language
     * @return Collection
     */
    public function getGroupTranslationsFor($language)
    {
        return $this->getGroupFilesFor($language)->mapWithKeys(function ($group) {
            return [$group->getBasename('.php') => $this->disk->getRequire($group->getPathname())];
        });
    }

    /**
     * Get all the translations for a given file
     *
     * @param string $language
     * @param string $file
     * @return array
     */
    public function getTranslationsForFile($language, $file)
    {
        $file = str_finish($file, '.php');
        $filePath = "{$this->languageFilesPath}/{$language}/{$file}";
        $translations = [];

        if ($this->disk->exists($filePath)) {
            return $this->disk->getRequire($filePath);
        }

        return [];
    }

    /**
     * Determine whether or not a language exists
     *
     * @param string $language
     * @return boolean
     */
    public function languageExists($language)
    {
        return $this->allLanguages()->contains($language);
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
        $this->saveGroupTranslations($language, $group, []);
    }

    /**
     * Save group type language translations
     *
     * @param string $language
     * @param string $group
     * @param array $translations
     * @return void
     */
    private function saveGroupTranslations($language, $group, $translations)
    {
        $this->disk->put("{$this->languageFilesPath}/{$language}/{$group}.php", "<?php\n\nreturn " . var_export($translations, true) . ';' . \PHP_EOL);
    }

    /**
     * Save single type language translations
     *
     * @param string $language
     * @param array $translations
     * @return void
     */
    private function saveSingleTranslations($language, $translations)
    {
        $this->disk->put(
            "{$this->languageFilesPath}/$language.json",
            json_encode((object)$translations, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
    }

    /**
     * Find all of the translations in the app without translation for a given language
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
     * Save all of the translations in the app without translation for a given language
     *
     * @param string $language
     * @return void
     */
    public function saveMissingTranslations($language = false)
    {
        $languages = $language ? [$language] : $this->allLanguages();

        foreach ($languages as $language) {
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
     * Get all the group files for a given language
     *
     * @param string $language
     * @return Collection
     */
    public function getGroupFilesFor($language)
    {
        return new Collection($this->disk->allFiles("{$this->languageFilesPath}/{$language}"));
    }

    /**
     * Get a collection of group names for a given language
     *
     * @param string $language
     * @return Collection
     */
    public function getGroupsFor($language)
    {
        return $this->getGroupFilesFor($language)->map(function ($file) {
            return $file->getBasename('.php');
        });
    }

    /**
     * Get all translations for a given language merged with the source language
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
            'single' => $singleTranslations
        ]);
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
        $filteredTranslations = new Collection;
        $allTranslations = $this->getSourceLanguageTranslationsWith(($language));
        if (!$filter) {
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
