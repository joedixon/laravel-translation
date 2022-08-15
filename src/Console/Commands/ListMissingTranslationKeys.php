<?php

namespace JoeDixon\Translation\Console\Commands;

class ListMissingTranslationKeys extends Command
{
    protected $signature = 'translation:list-missing-translation-keys';

    protected $description = 'List all of the translation keys in the app which don\'t have a corresponding translation';

    public function handle(): void
    {
        $rows = [];

        $missingTranslations = $this->translation->allLanguages()->mapWithKeys(
            fn ($language) => [$language => $this->translation->findMissingTranslations($language)]
        );

        // check whether or not there are any missing translations
        $isNotEmpty = $missingTranslations->first(function ($translations) {
            return $translations->get('short')->isNotEmpty() || $translations->get('string')->isNotEmpty();
        });

        // if no missing translations, inform the user and move on with your day
        if (! $isNotEmpty) {
            $this->info(__('translation::translation.no_missing_keys'));

            return;
        }

        // set some headers for the table of results
        $headers = [__('translation::translation.language'), __('translation::translation.type'), __('translation::translation.group'), __('translation::translation.key')];

        // iterate over each of the missing languages
        foreach ($missingTranslations as $language => $types) {
            // iterate over each of the file types (json or array)
            foreach ($types as $type => $keys) {
                // iterate over each of the keys
                foreach ($keys as $key => $value) {
                    // populate the array with the relevant data to fill the table
                    foreach ($value as $k => $v) {
                        $rows[] = [$language, $type, $key, $k];
                    }
                }
            }
        }

        // render the table of results
        $this->table($headers, $rows);
    }
}
