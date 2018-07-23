<?php

namespace JoeDixon\Translation\Console\Commands;

use Illuminate\Console\Command;

class ListMissingTranslations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translation:list-missing-translations';

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
        $translation = app()->make('translation');

        foreach ($translation->allLanguages() as $language) {
            $missingTranslations[$language] = $translation->findMissingTranslations($language);
        }

        $headers = [__('translation::translation.language'), __('translation::translation.type'), __('translation::translation.file'), __('translation::translation.key')];
        foreach ($missingTranslations as $language => $types) {
            foreach ($types as $type => $keys) {
                foreach ($keys as $key => $value) {
                    if (is_array($value)) {
                        foreach ($value as $k => $v) {
                            $rows[] = [$language, $type, "{$key}.php", $k];
                        }
                    } else {
                        $rows[] = [$language, $type, "{$language}.json", $key];
                    }
                }
            }
        }

        $this->table($headers, $rows);
    }
}
