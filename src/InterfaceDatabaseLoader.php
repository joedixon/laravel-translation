<?php

namespace JoeDixon\Translation;

use Illuminate\Translation\LoaderInterface;
use JoeDixon\Translation\Drivers\Translation;

class InterfaceDatabaseLoader implements LoaderInterface
{
    private $translation;

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
            return $this->translation->getSingleTranslationsFor($locale)->get('single', collect())->toArray();
        }

        if (is_null($namespace) || $namespace == '*') {
            return $this->translation->getGroupTranslationsFor($locale)->filter(function ($value, $key) use ($group) {
                return $key === $group;
            })->first();
        }

        return $this->translation->getGroupTranslationsFor($locale)->filter(function ($value, $key) use ($group, $namespace) {
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
