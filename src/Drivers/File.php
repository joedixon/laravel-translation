<?php

namespace JoeDixon\Translation\Drivers;

use Illuminate\Translation\FileLoader;
use Illuminate\Filesystem\Filesystem;
use RuntimeException;
use JoeDixon\Translation\Drivers\DriverInterface;
use JoeDixon\Translation\Drivers\Translation;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use JoeDixon\Translation\Exceptions\LanguageExistsException;

class File extends FileLoader implements DriverInterface
{
    use Translation;

    protected const POSSIBLE_ESTENSIONS = [
        'php',
        'json',
    ];

    /**
     * File extension in which we keep translation messages.
     *
     * @var string
     */
    protected $extension;

    /**
     * Create a new file loader instance.
     *
     * @param Filesystem $files
     * @param string $path
     * @return void
     */
    public function __construct(Filesystem $files, $path, $ext = null)
    {
        if (!$ext) {
            $driver = config('translation.driver', 'file');
            $ext = explode(':', $driver)[1] ?? 'php';
        }

        if (!in_array($ext, self::POSSIBLE_ESTENSIONS)) {
            throw new RuntimeException('Extension not possible');
        }

        $this->extension = $ext;

        parent::__construct($files, $path);
    }

    /**
     * Load a local namespaced translation group for overrides.
     *
     * @param array $lines
     * @param string $locale
     * @param string $group
     * @param string $namespace
     * @return array
     */
    protected function loadNamespaceOverrides(array $lines, $locale, $group, $namespace)
    {
        if ($this->files->exists($full = "{$this->path}/vendor/{$namespace}/{$locale}/{$group}.{$this->extension}")) {
            return array_replace_recursive($lines, $this->decode($full));
        }

        return $lines;
    }

    /**
     * Load a locale from a given path.
     *
     * @param string $path
     * @param string $locale
     * @param string $group
     * @return array
     */
    protected function loadPath($path, $locale, $group)
    {
        if ($this->files->exists($full = "{$path}/{$locale}/{$group}.{$this->extension}")) {
            return $this->decode($full);
        }

        return [];
    }

    /**
     * @param string $path
     * @return array
     */
    protected function decode(string $path): array
    {
        $method = 'get' . Str::studly($this->extension) . 'File';

        if (!method_exists($this, $method)) {
            throw new RuntimeException("No decode method for [$this->extension]");
        }

        return $this->{$method}($path);
    }

    /**
     * @param string $path
     * @return array
     */
    protected function getJsonFile(string $path): array
    {
        return json_decode($this->files->get($path), true);
    }

    /**
     * @param string $path
     * @return array
     */
    protected function getPhpFile(string $path): array
    {
        return $this->files->getRequire($path);
    }

    /**
     * @param string $path
     * @param array $translations
     * @return bool
     */
    protected function encode(string $path, array $translations): bool
    {
        $method = 'put' . Str::studly($this->extension) . 'File';

        if (!method_exists($this, $method)) {
            throw new RuntimeException("No encode method for [$this->extension]");
        }

        return $this->{$method}($path, $translations);
    }

    /**
     * @param string $path
     * @param array $translations
     * @return bool
     */
    protected function putJsonFile(string $path, array $translations): bool
    {
        return $this->files->put($path, json_encode($translations, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    /**
     * @param string $path
     * @param array $translations
     * @return bool
     */
    protected function putPhpFile(string $path, array $translations): bool
    {
        return $this->files->put($path, "<?php\n\nreturn ".var_export($translations, true).';');
    }

    /**
     * Get all group translations from the application.
     *
     * @return array
     */
    public function allGroup($language)
    {
        $groupPath = "{$this->path}" . DIRECTORY_SEPARATOR . "{$language}";

        if (! $this->files->exists($groupPath)) {
            return [];
        }

        $groups = Collection::make($this->files->allFiles($groupPath));

        return $groups->map(function ($group) {
            return $group->getBasename('.' . $this->extension);
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
                $vendor = Str::before(Str::after($group->getPathname(), 'vendor' . DIRECTORY_SEPARATOR), DIRECTORY_SEPARATOR);
                $content = $this->decode($group->getPathname());

                return [
                    $vendor . '::' . $group->getBasename('.' . $this->extension) => new Collection(Arr::dot($content))
                ];
            }

            $content = $this->decode($group->getPathname());
            return [
                $group->getBasename('.' . $this->extension) => new Collection(Arr::dot($content))
            ];
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
        $file = Str::finish($file, '.json');
        $filePath = "{$this->path}" . DIRECTORY_SEPARATOR . "{$language}" . DIRECTORY_SEPARATOR . "{$file}";
        $translations = [];

        if ($this->files->exists($filePath)) {
            $messages = json_decode($this->files->getRequire($filePath), true);
            $translations = Arr::dot($messages);
        }

        return $translations;
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

        $path = implode(DIRECTORY_SEPARATOR, [
            $this->path,
            $language,
            $group . '.' . $this->extension
        ]);

        $this->encode($path, $translations);
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
        $directory = implode(DIRECTORY_SEPARATOR, [
            $this->path,
            'vendor',
            $namespace,
            $language
        ]);

        if (! $this->files->exists($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }

        $path = $directory . DIRECTORY_SEPARATOR . $group . '.' . $this->extension;

        $this->encode($path, $translations);
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
        $directories = Collection::make($this->files->directories($this->path));

        return $directories->mapWithKeys(function ($directory) {
            $language = basename($directory);

            return [$language => $language];
        })->filter(function ($language) {
            // at the moemnt, we're not supporting vendor specific translations
            return $language != 'vendor';
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

        $this->files->makeDirectory("{$this->path}".DIRECTORY_SEPARATOR."$language");
        if (! $this->files->exists("{$this->path}".DIRECTORY_SEPARATOR."{$language}.json")) {
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
        $files = new Collection($this->files->allFiles($this->path));

        return $files->filter(function ($file) use ($language) {
            return strpos($file, "{$language}.json");
        })->flatMap(function ($file) {
            if (strpos($file->getPathname(), 'vendor')) {
                $vendor = Str::before(Str::after($file->getPathname(), 'vendor'.DIRECTORY_SEPARATOR), DIRECTORY_SEPARATOR);

                return ["{$vendor}::single" => new Collection(json_decode($this->files->get($file), true))];
            }

            return ['single' => new Collection(json_decode($this->files->get($file), true))];
        });
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
            $this->files->put(
                "{$this->path}".DIRECTORY_SEPARATOR."{$languageFilePath}",
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
        $groups = new Collection($this->files->allFiles("{$this->path}".DIRECTORY_SEPARATOR."{$language}"));
        // namespaced files reside in the vendor directory so we'll grab these
        // the `getVendorGroupFileFor` method
        $groups = $groups->merge($this->getVendorGroupFilesFor($language));

        $groups = $groups->filter(function ($file) {
            return $file->getExtension() == $this->extension;
        });

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

                return "{$vendor}::{$file->getBasename('.' . $this->extension)}";
            }

            return $file->getBasename('.' . $this->extension);
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
        if (! $this->files->exists("{$this->path}".DIRECTORY_SEPARATOR.'vendor')) {
            return;
        }

        $vendorGroups = [];
        foreach ($this->files->directories("{$this->path}".DIRECTORY_SEPARATOR.'vendor') as $vendor) {
            $vendor = Arr::last(explode(DIRECTORY_SEPARATOR, $vendor));
            if (! $this->files->exists("{$this->path}".DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR."{$vendor}".DIRECTORY_SEPARATOR."{$language}")) {
                array_push($vendorGroups, []);
            } else {
                array_push($vendorGroups, $this->files->allFiles("{$this->path}".DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR."{$vendor}".DIRECTORY_SEPARATOR."{$language}"));
            }
        }

        return new Collection(Arr::flatten($vendorGroups));
    }
}
