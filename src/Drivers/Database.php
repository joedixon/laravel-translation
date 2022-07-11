<?php

namespace JoeDixon\Translation\Drivers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use JoeDixon\Translation\Exceptions\LanguageExistsException;
use JoeDixon\Translation\Language;
use JoeDixon\Translation\Translation as TranslationModel;

class Database extends Translation
{
    public function __construct(protected $sourceLanguage, protected $scanner)
    {
    }

    /**
     * Get all languages.
     */
    public function allLanguages(): Collection
    {
        return Language::all()->mapWithKeys(function ($language) {
            return [$language->language => $language->name ?: $language->language];
        });
    }

    /**
     * Get all group translations for a given language.
     */
    public function allGroup(string $language): Collection
    {
        $groups = TranslationModel::getGroupsForLanguage($language);

        return $groups->map(function ($translation) {
            return $translation->group;
        });
    }

    /**
     * Get all translations.
     */
    public function allTranslations(): Collection
    {
        return $this->allLanguages()->mapWithKeys(function ($name, $language) {
            return [$language => $this->allTranslationsFor($language)];
        });
    }

    /**
     * Get all translations for a given language.
     */
    public function allTranslationsFor(string $language): Collection
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

        Language::create([
            'language' => $language,
            'name' => $name,
        ]);
    }

    /**
     * Add a group translation.
     */
    public function addGroupTranslation($language, $group, $key, $value = ''): void
    {
        if (!$this->languageExists($language)) {
            $this->addLanguage($language);
        }

        Language::where('language', $language)
            ->first()
            ->translations()
            ->updateOrCreate([
                'group' => $group,
                'key' => $key,
            ], [
                'group' => $group,
                'key' => $key,
                'value' => $value,
            ]);
    }

    /**
     * Add a single translation.
     */
    public function addSingleTranslation($language, $vendor, $key, $value = ''): void
    {
        if (!$this->languageExists($language)) {
            $this->addLanguage($language);
        }

        Language::where('language', $language)
            ->first()
            ->translations()
            ->updateOrCreate([
                'group' => $vendor,
                'key' => $key,
            ], [
                'key' => $key,
                'value' => $value,
            ]);
    }

    /**
     * Get single translations for a given language.
     */
    public function getSingleTranslationsFor(string $language): Collection
    {
        $translations = $this->getLanguage($language)
            ->translations()
            ->where('group', 'like', '%single')
            ->orWhereNull('group')
            ->get()
            ->groupBy('group');

        // if there is no group, this is a legacy translation so we need to
        // update to 'single'. We do this here so it only happens once.
        if ($this->hasLegacyGroups($translations->keys())) {
            TranslationModel::whereNull('group')->update(['group' => 'single']);
            // if any legacy groups exist, rerun the method so we get the
            // updated keys.
            return $this->getSingleTranslationsFor($language);
        }

        return $translations->map(function ($translations, $group) {
            return $translations->mapWithKeys(function ($translation) {
                return [$translation->key => $translation->value];
            });
        });
    }

    /**
     * Get group translations for a given language.
     */
    public function getGroupTranslationsFor(string $language): Collection
    {
        $translations = $this->getLanguage($language)
            ->translations()
            ->whereNotNull('group')
            ->where('group', 'not like', '%single')
            ->get()
            ->groupBy('group');

        return $translations->map(function ($translations) {
            return $translations->mapWithKeys(function ($translation) {
                return [$translation->key => $translation->value];
            });
        });
    }

    /**
     * Determine whether the given language exists.
     */
    public function languageExists(string $language): bool
    {
        return $this->getLanguage($language) ? true : false;
    }

    /**
     * Get all the groups for a given language.
     */
    public function getGroupsFor(string $language): Collection
    {
        return $this->allGroup($language);
    }

    /**
     * Get a language from the database.
     */
    private function getLanguage(string $language): ?Model
    {
        return Language::where('language', $language)->first();
    }

    /**
     * Determine if a set of single translations contains any legacy groups.
     * Previously, this was handled by setting the group value to NULL, now
     * we use 'single' to cater for vendor JSON language files.
     */
    private function hasLegacyGroups(Collection $groups): bool
    {
        return $groups->filter(function ($key) {
            return $key === '';
        })->count() > 0;
    }
}
