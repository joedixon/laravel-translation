<?php

namespace JoeDixon\Translation\Drivers;

use JoeDixon\Translation\Scanner;
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
     * @return array
     */
    public function allLanguages()
    {
        // As per the docs, there should be a subdirectory within the
        // languages path so we can return these directory names as an array
        return array_map(function ($language) {
            return array_last(explode('/', $language));
        }, $this->disk->directories($this->languageFilesPath));
    }

    /**
     * Get all the translations from the application
     *
     * @return array
     */
    public function allTranslations()
    {
        $translations = [];

        foreach ($this->allLanguages() as $language) {
            $translations[$language] = $this->allTranslationsFor($language);
        }

        return $translations;
    }

    /**
     * Get all translations for a particular language
     *
     * @param string $language
     * @return array
     */
    public function allTranslationsFor($language)
    {
        return [
            'json' => $this->getJsonTranslationsForLanguage($language),
            'array' => $this->getArrayTranslationsForLanguage($language)
        ];
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
            throw new LanguageExistsException('The language {' . $language . '} already exists.');
        }

        $this->disk->makeDirectory("{$this->languageFilesPath}/$language");
        if (!$this->disk->exists("{$this->languageFilesPath}/{$language}.json")) {
            $this->saveJsonTranslationFile($language, []);
        }
    }

    /**
     * Add a new array type translation
     *
     * @param string $language
     * @param string $key
     * @param string $value
     * @return void
     */
    public function addArrayTranslation($language, $key, $value = '')
    {
        list($file, $key) = explode('.', $key);
        $translations = $this->allTranslationsFor($language)['array'];

        // does the file exist? If not, create it.
        if (!array_key_exists($file, $translations)) {
            $translations[$file] = [];
        }

        // does the key exist? If so, throw an exception
        if (array_key_exists($key, $translations[$file])) {
            throw new LanguageKeyExistsException;
        }

        $translations[$file][$key] = $value;

        $this->saveArrayTranslationFile($language, $file, $translations[$file]);
    }

    /**
     * Add a new JSON type translations
     *
     * @param string $language
     * @param string $key
     * @param string $value
     * @return void
     */
    public function addJsonTranslation($language, $key, $value = '')
    {
        $translations = $this->allTranslationsFor($language)['json'];

        // does the key exist? If so, throw an exception
        if (array_key_exists($key, $translations)) {
            throw new LanguageKeyExistsException;
        }

        $translations[$key] = $value;

        $this->saveJsonTranslationFile($language, $translations);
    }

    /**
     * Get all of the JSON translations for a given language
     *
     * @param string $language
     * @return array
     */
    public function getJsonTranslationsForLanguage($language)
    {
        $jsonPath = $this->languageFilesPath . "/$language.json";

        if ($this->disk->exists($jsonPath)) {
            return json_decode($this->disk->get($jsonPath), true);
        }

        return [];
    }

    /**
     * Get all of the array translations for a given language
     *
     * @param string $language
     * @return varrayoid
     */
    public function getArrayTranslationsForLanguage($language)
    {
        $arrayPath = "{$this->languageFilesPath}/{$language}";
        $translations = [];

        if ($this->disk->exists($arrayPath)) {
            foreach ($this->disk->allFiles($arrayPath) as $file) {
                $translations[$file->getBasename('.php')] = $this->disk->getRequire($file->getPathname());
            }

            return $translations;
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
        return in_array($language, $this->allLanguages());
    }

    /**
     * Add a new array type language file
     *
     * @param string $language
     * @param string $filename
     * @return void
     */
    public function addArrayTranslationFile($language, $filename)
    {
        $this->saveArrayTranslationFile($language, $filename, []);
    }

    /**
     * Save array type language file
     *
     * @param string $language
     * @param string $filename
     * @param array $translations
     * @return void
     */
    private function saveArrayTranslationFile($language, $filename, $translations)
    {
        $this->disk->put("{$this->languageFilesPath}/{$language}/{$filename}.php", "<?php\n\nreturn " . var_export($translations, true) . ';' . \PHP_EOL);
    }

    /**
     * Save JSON type language file
     *
     * @param string $language
     * @param array $translations
     * @return void
     */
    private function saveJsonTranslationFile($language, $translations)
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
            if (isset($missingTranslations['json'])) {
                foreach ($missingTranslations['json'] as $key => $value) {
                    $this->addJsonTranslation($language, $key);
                }
            }

            if (isset($missingTranslations['array'])) {
                foreach ($missingTranslations['array'] as $file => $keys) {
                    foreach ($keys as $key => $value) {
                        $this->addArrayTranslation($language, "{$file}.{$key}");
                    }
                }
            }
        }
    }
}
