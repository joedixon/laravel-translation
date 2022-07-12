<?php

namespace JoeDixon\Translation\Drivers\File;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

trait InteractsWithShortKeys
{
    /**
     * Get short key translations for a given language.
     */
    public function allShortKeyTranslationsFor(string $language): Collection
    {
        return $this->allShortKeyFilesFor($language)->mapWithKeys(function ($group) {
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
     * Get all the short key groups for a given language.
     */
    public function allShortKeyGroupsFor(string $language): Collection
    {
        return $this->allShortKeyFilesFor($language)->map(function ($file) {
            if (Str::contains($file->getPathname(), 'vendor')) {
                $vendor = Str::before(Str::after($file->getPathname(), 'vendor'.DIRECTORY_SEPARATOR), DIRECTORY_SEPARATOR);

                return "{$vendor}::{$file->getBasename('.php')}";
            }

            return $file->getBasename('.php');
        });
    }

    /**
     * Add a short key translation.
     */
    public function addShortKeyTranslation(string $language, string $group, string $key, string $value = ''): void
    {
        if (! $this->languageExists($language)) {
            $this->addLanguage($language);
        }

        $translations = $this->allShortKeyTranslationsFor($language);

        // does the group exist? If not, create it.
        if (! $translations->keys()->contains($group)) {
            $translations->put($group, collect());
        }

        $values = $translations->get($group);
        $values[$key] = $value;
        $translations->put($group, collect($values));

        $this->saveShortKeyTranslations($language, $group, new Collection($translations->get($group)));
    }

    /**
     * Add a new group of short key translations.
     */
    protected function addShortKeyGroup(string $language, string $group): void
    {
        $this->saveShortKeyTranslations($language, $group, new Collection);
    }

    /**
     * Get all the short key files for a given language.
     */
    protected function allShortKeyFilesFor(string $language): Collection
    {
        $groups = new Collection($this->disk->allFiles("{$this->languageFilesPath}".DIRECTORY_SEPARATOR."{$language}"));
        // namespaced files reside in the vendor directory so we'll grab these
        // the `getVendorGroupFileFor` method
        $groups = $groups->merge($this->allVendorShortKeyFilesFor($language));

        return $groups;
    }

    /**
     * Get all the vendor short key files for a given language.
     */
    protected function allVendorShortKeyFilesFor(string $language): ?Collection
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

    /**
     * Save short key translations.
     */
    protected function saveShortKeyTranslations(string $language, string $group, Collection $translations): void
    {
        // here we check if it's a namespaced translation which need saving to a
        // different path
        if (Str::contains($group, '::')) {
            $this->saveNamespacedShortKeyTranslations($language, $group, $translations);

            return;
        }

        $this->disk->put("{$this->languageFilesPath}".DIRECTORY_SEPARATOR."{$language}".DIRECTORY_SEPARATOR."{$group}.php", "<?php\n\nreturn ".var_export($translations->toArray(), true).';'.\PHP_EOL);
    }

    /**
     * Save namespaced short key translations.
     */
    protected function saveNamespacedShortKeyTranslations(string $language, string $group, Collection $translations): void
    {
        [$namespace, $group] = explode('::', $group);
        $directory = "{$this->languageFilesPath}".DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR."{$namespace}".DIRECTORY_SEPARATOR."{$language}";

        if (! $this->disk->exists($directory)) {
            $this->disk->makeDirectory($directory, 0755, true);
        }

        $this->disk->put("$directory".DIRECTORY_SEPARATOR."{$group}.php", "<?php\n\nreturn ".var_export($translations->toArray(), true).';'.\PHP_EOL);
    }
}
