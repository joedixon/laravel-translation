<?php

namespace JoeDixon\Translation\Drivers\File;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use JoeDixon\Translation\Drivers\CombinedTranslations;
use JoeDixon\Translation\Drivers\Translation;
use JoeDixon\Translation\Exceptions\LanguageExistsException;
use JoeDixon\Translation\Scanner;

class File extends Translation
{
    use InteractsWithStringKeys, InteractsWithShortKeys;

    public function __construct(
        private Filesystem $disk,
        private string $languageFilesPath,
        protected string $sourceLanguage,
        protected Scanner $scanner
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
     * Determine whether the given language exists.
     */
    public function languageExists(string $language): bool
    {
        return $this->allLanguages()->contains($language);
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
            $this->saveStringKeyTranslations($language, collect(['string' => new Collection()]));
        }
    }
}
