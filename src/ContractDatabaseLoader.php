<?php

namespace JoeDixon\Translation;

use Illuminate\Contracts\Translation\Loader;
use Illuminate\Support\Collection;
use JoeDixon\Translation\Drivers\Translation;

class ContractDatabaseLoader implements Loader
{
    private Translation $translation;

    public function __construct(Translation $translation)
    {
        $this->translation = $translation;
    }

    /**
     * Load the messages for the given locale.
     *
     * @param  string  $locale
     * @param  string  $group
     * @param  string  $namespace
     * @return array
     */
    public function load($locale, $group, $namespace = null)
    {
        if ($group == '*' && $namespace == '*') {
            return $this->translation->allStringKeyTranslationsFor($locale)->get('single', new Collection())->toArray();
        }

        if (is_null($namespace) || $namespace == '*') {
            return $this->translation->allShortKeyTranslationsFor($locale)->filter(function ($value, $key) use ($group) {
                return $key === $group;
            })->first();
        }

        return $this->translation->allShortKeyTranslationsFor($locale)->filter(function ($value, $key) use ($group, $namespace) {
            return $key === "{$namespace}::{$group}";
        })->first();
    }

    /**
     * Add a new namespace to the loader.
     *
     * @param  string  $namespace
     * @param  string  $hint
     * @return void
     */
    public function addNamespace($namespace, $hint)
    {
        //
    }

    /**
     * Add a new JSON path to the loader.
     *
     * @param  string  $path
     * @return void
     */
    public function addJsonPath($path)
    {
        //
    }

    /**
     * Get an array of all the registered namespaces.
     *
     * @return array
     */
    public function namespaces()
    {
        return [];
    }
}
