<?php

namespace JoeDixon\Translation\Drivers\File;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

trait InteractsWithStringKeys
{
    /**
     * Get string key translations for a given language.
     */
    public function allStringKeyTranslationsFor(string $language): Collection
    {
        $files = new Collection($this->disk->allFiles($this->languageFilesPath));

        return $files->filter(
            fn ($file) => str_ends_with($file, "{$language}.json")
        )->flatMap(function ($file) {
            if (strpos($file->getPathname(), 'vendor')) {
                $vendor = Str::before(Str::after($file->getPathname(), 'vendor'.DIRECTORY_SEPARATOR), DIRECTORY_SEPARATOR);

                return ["{$vendor}::string" => new Collection(json_decode($this->disk->get($file), true))];
            }

            return ['string' => new Collection(json_decode($this->disk->get($file), true))];
        });
    }

    /**
     * Add a string key translation.
     */
    public function addStringKeyTranslation(string $language, string $vendor, string $key, string $value = ''): void
    {
        if (! $this->languageExists($language)) {
            $this->addLanguage($language);
        }

        $translations = $this->allStringKeyTranslationsFor($language);
        $translations->get($vendor) ?: $translations->put($vendor, new Collection());
        $translations->get($vendor)->put($key, $value);

        $this->saveStringKeyTranslations($language, $translations);
    }

    /**
     * Save string key translations.
     */
    private function saveStringKeyTranslations(string $language, Collection $translations): void
    {
        foreach ($translations as $group => $translation) {
            $vendor = Str::before($group, '::string');
            $languageFilePath = $vendor !== 'string' ? 'vendor'.DIRECTORY_SEPARATOR."{$vendor}".DIRECTORY_SEPARATOR."{$language}.json" : "{$language}.json";
            $json = json_encode((object) $translations->get($group), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            if ($json === false) {
                continue;
            }

            $this->disk->put(
                "{$this->languageFilesPath}".DIRECTORY_SEPARATOR."{$languageFilePath}",
                $json
            );
        }
    }
}
