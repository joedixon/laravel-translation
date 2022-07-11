<?php

namespace JoeDixon\Translation\Drivers;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use JoeDixon\Translation\Exceptions\LanguageExistsException;

class File extends Translation
{
    public function __construct(
        private Filesystem $disk,
        private $languageFilesPath,
        protected $sourceLanguage,
        protected $scanner
    ) {
    }

    /**
     * Get all languages.
     */
    public function allLanguages(): Collection
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
     * Get all group translations for a given language.
     */
    public function allGroup(string $language): Collection
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
     * Get all translations.
     */
    public function allTranslations(): Collection
    {
        return $this->allLanguages()->mapWithKeys(function ($language) {
            return [$language => $this->allTranslationsFor($language)];
        });
    }

    /**
     * Get all translations for a given language.
     */
    public function allTranslationsFor($language): Collection
    {
        return Collection::make([
            'group' => $this->getGroupTranslationsFor($language),
            'single' => $this->getSingleTranslationsFor($language),
        ]);
    }

    /**
     * Add a new language.
     */
    public function addLanguage(string $language, ?string $name = null): void
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
     * Add a group translation.
     */
    public function addGroupTranslation(string $language, string $group, string $key, string $value = ''): void
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

        $this->saveGroupTranslations($language, $group, new Collection($translations->get($group)));
    }

    /**
     * Add a single translation.
     */
    public function addSingleTranslation(string $language, string $vendor, string $key, string $value = ''): void
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
     * Get single translations for a given language.
     */
    public function getSingleTranslationsFor(string $language): Collection
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
     * Get group translations for a given language.
     */
    public function getGroupTranslationsFor(string $language): Collection
    {
        return $this->getGroupFilesFor($language)->mapWithKeys(function ($group) {
            // here we check if the path contains 'vendor' as these will be the
            // files which need namespacing
            if (Str::contains($group->getPathname(), 'vendor')) {
                $vendor = Str::before(Str::after($group->getPathname(), 'vendor'.DIRECTORY_SEPARATOR), DIRECTORY_SEPARATOR);

                return ["{$vendor}::{$group->getBasename('.php')}" => new Collection($this->disk->getRequire($group->getPathname()))];
            }

            return [$group->getBasename('.php') => new Collection($this->disk->getRequire($group->getPathname()))];
        });
    }

    /**
     * Determine whether the given language exists.
     */
    public function languageExists(string $language): bool
    {
        return $this->allLanguages()->contains($language);
    }

    /**
     * Get all the groups for a given language.
     */
    public function getGroupsFor(string $language): Collection
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
     * Get all the translations for a given file.
     */
    public function getTranslationsForFile(string $language, string $file): Collection
    {
        $file = Str::finish($file, '.php');
        $filePath = "{$this->languageFilesPath}".DIRECTORY_SEPARATOR."{$language}".DIRECTORY_SEPARATOR."{$file}";
        $translations = new Collection;

        if ($this->disk->exists($filePath)) {
            $translations = new Collection($this->disk->getRequire($filePath));
        }

        return $translations;
    }

    /**
     * Add a new group of translations.
     */
    public function addGroup(string $language, string $group): void
    {
        $this->saveGroupTranslations($language, $group, new Collection);
    }

    /**
     * Save group type language translations.
     */
    public function saveGroupTranslations(string $language, string $group, Collection $translations): void
    {
        // here we check if it's a namespaced translation which need saving to a
        // different path
        if (Str::contains($group, '::')) {
            $this->saveNamespacedGroupTranslations($language, $group, $translations);

            return;
        }
        $this->disk->put("{$this->languageFilesPath}".DIRECTORY_SEPARATOR."{$language}".DIRECTORY_SEPARATOR."{$group}.php", "<?php\n\nreturn ".var_export($translations->toArray(), true).';'.\PHP_EOL);
    }

    /**
     * Save namespaced group type language translations.
     */
    private function saveNamespacedGroupTranslations(string $language, string $group, Collection $translations): void
    {
        [$namespace, $group] = explode('::', $group);
        $directory = "{$this->languageFilesPath}".DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR."{$namespace}".DIRECTORY_SEPARATOR."{$language}";

        if (! $this->disk->exists($directory)) {
            $this->disk->makeDirectory($directory, 0755, true);
        }

        $this->disk->put("$directory".DIRECTORY_SEPARATOR."{$group}.php", "<?php\n\nreturn ".var_export($translations->toArray(), true).';'.\PHP_EOL);
    }

    /**
     * Save single type language translations.
     */
    private function saveSingleTranslations(string $language, Collection $translations): void
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
     */
    public function getGroupFilesFor(string $language): Collection
    {
        $groups = new Collection($this->disk->allFiles("{$this->languageFilesPath}".DIRECTORY_SEPARATOR."{$language}"));
        // namespaced files reside in the vendor directory so we'll grab these
        // the `getVendorGroupFileFor` method
        $groups = $groups->merge($this->getVendorGroupFilesFor($language));

        return $groups;
    }

    /**
     * Get all the vendor group files for a given language.
     */
    public function getVendorGroupFilesFor(string $language): ?Collection
    {
        if (! $this->disk->exists("{$this->languageFilesPath}".DIRECTORY_SEPARATOR.'vendor')) {
            return null;
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
