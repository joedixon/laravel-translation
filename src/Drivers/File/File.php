<?php

namespace JoeDixon\Translation\Drivers\File;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
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

    public function map($key = null, $default = null): Collection|string|null
    {
        $map = Collection::make($this->disk->allFiles($this->languageFilesPath))
            ->flatMap(function ($file) {
                $path = Str::of($file->getPathname())
                    ->replace($this->languageFilesPath, '')
                    ->replaceFirst(DIRECTORY_SEPARATOR, '');

                $key = Str::of($path)
                    ->replaceLast(".{$file->getExtension()}", '')
                    ->replace(DIRECTORY_SEPARATOR, '.', $path);

                return [(string) $key => (string) $path];
            });

        if ($key) {
            return $map->get($key, $default);
        }

        return $map;
    }

    /**
     * Get all languages.
     */
    public function allLanguages(): Collection
    {
        // As per the docs, there should be a subdirectory within the
        // languages path so we can return these directory names as a collection
        $directories = Collection::make($this->disk->directories($this->languageFilesPath));

        $directoryLanguages = $directories->mapWithKeys(function ($directory) {
            $language = basename($directory);

            return [$language => $language];
        })->filter(function ($language) {
            // at the moemnt, we're not supporting vendor specific translations
            return $language != 'vendor';
        });

        $fileLangauges = Collection::make($this->disk->allFiles($this->languageFilesPath))
            ->filter(fn ($file) => $file->getExtension() === 'json')
            ->mapWithKeys(fn ($file) => [Str::replace(".{$file->getExtension()}", '', $file->getFilename()) => Str::replace(".{$file->getExtension()}", '', $file->getFilename())]);

        return $directoryLanguages->merge($fileLangauges);
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

    public function allTranslationsFromMap(string $key): Collection
    {
        if (! $file = $this->map($key)) {
            return new Collection();
        }

        if (Str::endsWith($file, '.php')) {
            return Collection::make($this->disk->getRequire("{$this->languageFilesPath}/{$file}"));
        }

        return Collection::make(json_decode($this->disk->get("{$this->languageFilesPath}/{$file}"), true));
    }
}
