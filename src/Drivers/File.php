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

    private $scanner;

    public function __construct(Filesystem $disk, $languageFilesPath)
    {
        $this->disk = $disk;
        $this->languageFilesPath = $languageFilesPath;
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
        $arrayPath = "{$this->languageFilesPath}/{$language}";

        if (!$this->disk->exists($arrayPath)) {
            return [];
        }

        $files = Collection::make($this->disk->allFiles($arrayPath));

        return $files->map(function ($file) {
            return $file->getBasename('.php');
        });
    }

    /**
     * Get all the translations from the application
     *
     * @return array
     */
    public function allTranslations()
    {
        $translations = new Collection;

        $this->allLanguages()->each(function ($language) use ($translations) {
            $translations->put($language, $this->allTranslationsFor($language));
        });

        return $translations;
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
            'group' => $this->getGroupTranslationsForLanguage($language),
            'single' => $this->getSingleTranslationsForLanguage($language)
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
            $this->saveSingleTranslationFile($language, []);
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

        list($file, $key) = explode('.', $key);
        $translations = $this->getGroupTranslationsForLanguage($language);

        // does the file exist? If not, create it.
        if (!$translations->keys()->contains($file)) {
            $translations->put($file, []);
        }

        // does the key exist? If so, throw an exception
        if (array_key_exists($key, $translations->get($file))) {
            throw new LanguageKeyExistsException(__('translation::errors.key_exists', ['key' => "{$file}.{$key}"]));
        }

        $values = $translations->get($file);
        $values[$key] = $value;
        $translations->put($file, $values);

        // var_dump($translations);

        $this->saveGroupTranslationFile($language, $file, $translations->get($file));
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

        $translations = $this->getSingleTranslationsForLanguage($language);

        // does the key exist? If so, throw an exception
        if (array_key_exists($key, $translations)) {
            throw new LanguageKeyExistsException(__('translation::errors.key_exists', ['key' => $key]));
        }

        $translations->put($key, $value);

        $this->saveSingleTranslationFile($language, $translations);
    }

    /**
     * Get all of the single translations for a given language
     *
     * @param string $language
     * @return Collection
     */
    public function getSingleTranslationsForLanguage($language)
    {
        $jsonPath = $this->languageFilesPath . "/$language.json";

        if ($this->disk->exists($jsonPath)) {
            return new Collection(json_decode($this->disk->get($jsonPath), true));
        }

        return new Collection;
    }

    /**
     * Get all of the group translations for a given language
     *
     * @param string $language
     * @return Collection
     */
    public function getGroupTranslationsForLanguage($language)
    {
        $arrayPath = "{$this->languageFilesPath}/{$language}";
        $translations = new Collection;

        if (!$this->disk->exists($arrayPath)) {
            return new $translations;
        }

        $files = new Collection($this->disk->allFiles($arrayPath));

        $files->each(function ($file) use ($translations) {
            $translations->put($file->getBasename('.php'), $this->disk->getRequire($file->getPathname()));
        });

        return $translations;
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
     * Add a new group type language file
     *
     * @param string $language
     * @param string $filename
     * @return void
     */
    public function addGroupTranslationFile($language, $filename)
    {
        $this->saveGroupTranslationFile($language, $filename, []);
    }

    /**
     * Save group type language file
     *
     * @param string $language
     * @param string $filename
     * @param array $translations
     * @return void
     */
    private function saveGroupTranslationFile($language, $filename, $translations)
    {
        $this->disk->put("{$this->languageFilesPath}/{$language}/{$filename}.php", "<?php\n\nreturn " . var_export($translations, true) . ';' . \PHP_EOL);
    }

    /**
     * Save single type language file
     *
     * @param string $language
     * @param array $translations
     * @return void
     */
    private function saveSingleTranslationFile($language, $translations)
    {
        $this->disk->put(
            "{$this->languageFilesPath}/$language.json",
            json_encode((object)$translations, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
    }

    /**
     * Find all of the translations in the app without entry in language file
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
     * Save all of the translations in the app without entry in language file
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
                foreach ($missingTranslations['group'] as $file => $keys) {
                    foreach ($keys as $key => $value) {
                        $this->addGroupTranslation($language, "{$file}.{$key}");
                    }
                }
            }
        }
    }

    public function merge($translations, $language)
    {
        foreach ($translations as $key => $value) {
            $translations[$key] = [$value, $this->findKey($this->allTranslationsFor($language), $key)];
        }

        return $translations;
    }

    public function findKey($array, $keySearch)
    {
        foreach ($array as $key => $item) {
            if ($key == $keySearch) {
                return $item;
            } elseif (is_array($item)) {
                return $this->findKey($item, $keySearch);
            }
        }
        return '';
    }
}
