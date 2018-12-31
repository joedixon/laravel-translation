<?php

namespace JoeDixon\Translation\Console\Commands;

class ListMissingTranslationKeys extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translation:list-missing-translation-keys';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all of the translation keys in the app which don\'t have a corresponding translation';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $missingTranslations = [];
        $rows = [];

        foreach ($this->translation->allLanguages() as $language => $name) {
            $missingTranslations[$language] = $this->translation->findMissingTranslations($language);
        }

        // check whether or not there are any missing translations
        $empty = true;
        foreach ($missingTranslations as $language => $values) {
            if (! empty($values)) {
                $empty = false;
            }
        }

        // if no missing translations, inform the user and move on with your day
        if ($empty) {
            return $this->info(__('translation::translation.no_missing_keys'));
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
