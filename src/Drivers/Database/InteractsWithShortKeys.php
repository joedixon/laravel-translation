<?php

namespace JoeDixon\Translation\Drivers\Database;

use Illuminate\Support\Collection;
use JoeDixon\Translation\Language;
use JoeDixon\Translation\Translation;

trait InteractsWithShortKeys
{
    /**
     * Get short key translations for a given language.
     */
    public function allShortKeyTranslationsFor(string $language): Collection
    {
        $translations = $this->getLanguage($language)
            ->translations()
            ->whereNotNull('group')
            ->where('group', 'not like', '%string')
            ->get()
            ->groupBy('group');

        return $translations->map(function ($translations) {
            return $translations->mapWithKeys(function ($translation) {
                return [$translation->key => $translation->value];
            });
        });
    }

    /**
     * Get all the short key groups for a given language.
     *
     * @return Collection<string>
     */
    public function allShortKeyGroupsFor(string $language): Collection
    {
        return Translation::getGroupsForLanguage($language)
            ->map(
                fn (Translation $translation): string => $translation->group
            );
    }

    /**
     * Add a short key translation.
     */
    public function addShortKeyTranslation(string $language, string $group, string $key, string $value = ''): void
    {
        if (! $this->languageExists($language)) {
            $this->addLanguage($language);
        }

        $this->getLanguage($language)
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
     * Determine if a set of single translations contains any legacy groups.
     * Previously, this was handled by setting the group value to NULL, now
     * we use 'single' to cater for vendor JSON language files.
     */
    protected function hasLegacyGroups(Collection $groups): bool
    {
        return $groups->filter(function ($key) {
            return $key === '';
        })->count() > 0;
    }
}
