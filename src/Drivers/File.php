<?php

namespace JoeDixon\Translation\Drivers;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use JoeDixon\Translation\Exceptions\LanguageExistsException;

class File extends Translation implements DriverInterface
{
    private $disk;

    private $languageFilesPath;

    protected $sourceLanguage;

    protected $scanner;

    public function __construct(Filesystem $disk, $languageFilesPath, $sourceLanguage, $scanner)
    {
        $this->disk = $disk;
        $this->languageFilesPath = $languageFilesPath;
        $this->sourceLanguage = $sourceLanguage;
        $this->scanner = $scanner;
    }

    /**
     * Get all languages from the application.
     *
     * @return Collection
     */
    public function allLanguages()
    {
        // As per the docs, there should be a subdirectory within the
        // languages path so we can return these directory names as a collection
        $directories = Collection::make($this->disk->directories($this->languageFilesPath));

        return $directories->mapWithKeys(function ($directory) {
            $language = basename($directory);

            return [$language => $language];
        })->filter(function ($language) {
            // at the moemnt, we're not supporting vendor specific translations
            return $language != 'vendor';
        });
    }

    /**
     * Get all group translations from the application.
     *
     * @return array
     */
    public function allGroup($language)
    {
        $groupPath = "{$this->languageFilesPath}".DIRECTORY_SEPARATOR."{$language}";

        if (! $this->disk->exists($groupPath)) {
            return [];
        }

        $groups = Collection::make($this->disk->allFiles($groupPath));

        return $groups->map(function ($group) {
            return $group->getBasename('.php');
        });
    }

    /**
     * Get all the translations from the application.
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
     * Get all translations for a particular language.
     *
     * @param string $language
     * @return Collection
     */
    public function allTranslationsFor($language)
    {
        return Collection::make([
            'group' => $this->getGroupTranslationsFor($language),
            'single' => $this->getSingleTranslationsFor($language),
        ]);
    }

    /**
     * Add a new language to the application.
     *
     * @param string $language
     * @return void
     */
    public function addLanguage($language, $name = null)
    {
        if ($this->languageExists($language)) {
            throw new LanguageExistsException(__('translation::errors.language_exists', ['language' => $language]));
        }

        $this->disk->makeDirectory("{$this->languageFilesPath}".DIRECTORY_SEPARATOR."$language");
        if (! $this->disk->exists("{$this->languageFilesPath}".DIRECTORY_SEPARATOR."{$language}.json")) {
            $this->saveSingleTranslations($language, collect(['single' => collect()]));
        }
    }

    /**
     * Add a new group type translation.
     *
     * @param string $language
     * @param string $key
     * @param string $value
     * @return void
     */
    public function addGroupTranslation($language, $group, $key, $value = '')
    {
        if (! $this->languageExists($language)) {
            $this->addLanguage($language);
        }

        $translations = $this->getGroupTranslationsFor($language);

        // does the group exist? If not, create it.
        if (! $translations->keys()->contains($group)) {
            $translations->put($group, collect());
        }

        $values = $translations->get($group);
        $values[$key] = $value;
        $translations->put($group, collect($values));

        $this->saveGroupTranslations($language, $group, $translations->get($group));
    }

    /**
     * Add a new single type translation.
     *
     * @param string $language
     * @param string $key
     * @param string $value
     * @return void
     */
    public function addSingleTranslation($language, $vendor, $key, $value = '')
    {
        if (! $this->languageExists($language)) {
            $this->addLanguage($language);
        }

        $translations = $this->getSingleTranslationsFor($language);
        $translations->get($vendor) ?: $translations->put($vendor, collect());
        $translations->get($vendor)->put($key, $value);

        $this->saveSingleTranslations($language, $translations);
    }

    /**
     * Get all of the single translations for a given language.
     *
     * @param string $language
     * @return Collection
     */
    public function getSingleTranslationsFor($language)
    {
        $files = new Collection($this->disk->allFiles($this->languageFilesPath));

        return $files->filter(function ($file) use ($language) {
            return strpos($file, "{$language}.json");
        })->flatMap(function ($file) {
            if (strpos($file->getPathname(), 'vendor')) {
                $vendor = Str::before(Str::after($file->getPathname(), 'vendor'.DIRECTORY_SEPARATOR), DIRECTORY_SEPARATOR);

                return ["{$vendor}::single" => new Collection(json_decode($this->disk->get($file), true))];
            }

            return ['single' => new Collection(json_decode($this->disk->get($file), true))];
        });
    }

    /**
     * Get all of the group translations for a given language.
     *
     * @param string $language
     * @return Collection
     */
    public function getGroupTranslationsFor($language)
    {
        return $this->getGroupFilesFor($language)->mapWithKeys(function ($group) {
            // here we check if the path contains 'vendor' as these will be the
            // files which need namespacing
            if (Str::contains($group->getPathname(), 'vendor')) {
                $vendor = Str::before(Str::after($group->getPathname(), 'vendor'.DIRECTORY_SEPARATOR), DIRECTORY_SEPARATOR);

                return ["{$vendor}::{$group->getBasename('.php')}" => new Collection(Arr::dot($this->disk->getRequire($group->getPathname())))];
            }

            return [$group->getBasename('.php') => new Collection(Arr::dot($this->disk->getRequire($group->getPathname())))];
        });
    }

    /**
     * Get all the translations for a given file.
     *
     * @param string $language
     * @param string $file
     * @return array
     */
    public function getTranslationsForFile($language, $file)
    {
        $file = Str::finish($file, '.php');
        $filePath = "{$this->languageFilesPath}".DIRECTORY_SEPARATOR."{$language}".DIRECTORY_SEPARATOR."{$file}";
        $translations = [];

        if ($this->disk->exists($filePath)) {
            $translations = Arr::dot($this->disk->getRequire($filePath));
        }

        return $translations;
    }

    /**
     * Determine whether or not a language exists.
     *
     * @param string $language
     * @return bool
     */
    public function languageExists($language)
    {
        return $this->allLanguages()->contains($language);
    }

    /**
     * Add a new group of translations.
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
     * Save group type language translations.
     *
     * @param string $language
     * @param string $group
     * @param array $translations
     * @return void
     */
    public function saveGroupTranslations($language, $group, $translations)
    {
        // here we check if it's a namespaced translation which need saving to a
        // different path
        $translations = $translations instanceof Collection ? $translations->toArray() : $translations;
        ksort($translations);
        $translations = array_undot($translations);
        if (Str::contains($group, '::')) {
            return $this->saveNamespacedGroupTranslations($language, $group, $translations);
        }
        $this->disk->put("{$this->languageFilesPath}".DIRECTORY_SEPARATOR."{$language}".DIRECTORY_SEPARATOR."{$group}.php", "<?php\n\nreturn ".var_export($translations, true).';'.\PHP_EOL);
    }

    /**
     * Save namespaced group type language translations.
     *
     * @param string $language
     * @param string $group
     * @param array $translations
     * @return void
     */
    private function saveNamespacedGroupTranslations($language, $group, $translations)
    {
        [$namespace, $group] = explode('::', $group);
        $directory = "{$this->languageFilesPath}".DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR."{$namespace}".DIRECTORY_SEPARATOR."{$language}";

        if (! $this->disk->exists($directory)) {
            $this->disk->makeDirectory($directory, 0755, true);
        }

        $this->disk->put("$directory".DIRECTORY_SEPARATOR."{$group}.php", "<?php\n\nreturn ".var_export($translations, true).';'.\PHP_EOL);
    }

    /**
     * Save single type language translations.
     *
     * @param string $language
     * @param array $translations
     * @return void
     */
    private function saveSingleTranslations($language, $translations)
    {
        foreach ($translations as $group => $translation) {
            $vendor = Str::before($group, '::single');
            $languageFilePath = $vendor !== 'single' ? 'vendor'.DIRECTORY_SEPARATOR."{$vendor}".DIRECTORY_SEPARATOR."{$language}.json" : "{$language}.json";
            $this->disk->put(
                "{$this->languageFilesPath}".DIRECTORY_SEPARATOR."{$languageFilePath}",
                json_encode((object) $translations->get($group), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            );
        }
    }

    /**
     * Get all the group files for a given language.
     *
     * @param string $language
     * @return Collection
     */
    public function getGroupFilesFor($language)
    {
        $groups = new Collection($this->disk->allFiles("{$this->languageFilesPath}".DIRECTORY_SEPARATOR."{$language}"));
        // namespaced files reside in the vendor directory so we'll grab these
        // the `getVendorGroupFileFor` method
        $groups = $groups->merge($this->getVendorGroupFilesFor($language));

        return $groups;
    }

    /**
     * Get a collection of group names for a given language.
     *
     * @param string $language
     * @return Collection
     */
    public function getGroupsFor($language)
    {
        return $this->getGroupFilesFor($language)->map(function ($file) {
            if (Str::contains($file->getPathname(), 'vendor')) {
                $vendor = Str::before(Str::after($file->getPathname(), 'vendor'.DIRECTORY_SEPARATOR), DIRECTORY_SEPARATOR);

                return "{$vendor}::{$file->getBasename('.php')}";
            }

            return $file->getBasename('.php');
        });
    }

    /**
     * Get all the vendor group files for a given language.
     *
     * @param string $language
     * @return Collection
     */
    public function getVendorGroupFilesFor($language)
    {
        if (! $this->disk->exists("{$this->languageFilesPath}".DIRECTORY_SEPARATOR.'vendor')) {
            return;
        }

        $vendorGroups = [];
        foreach ($this->disk->directories("{$this->languageFilesPath}".DIRECTORY_SEPARATOR.'vendor') as $vendor) {
            $vendor = Arr::last(explode(DIRECTORY_SEPARATOR, $vendor));
            if (! $this->disk->exists("{$this->languageFilesPath}".DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR."{$vendor}".DIRECTORY_SEPARATOR."{$language}")) {
                array_push($vendorGroups, []);
            } else {
                array_push($vendorGroups, $this->disk->allFiles("{$this->languageFilesPath}".DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR."{$vendor}".DIRECTORY_SEPARATOR."{$language}"));
            }
        }

        return new Collection(Arr::flatten($vendorGroups));
    }
}
