<?php

namespace JoeDixon\Translation\Console\Commands;

use Illuminate\Console\Command;

class SynchroniseMissingTranslationKeys extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translation:sync-missing-tranlsation-keys {language?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add all of the missing tranlation keys for all languages or a single language';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $translation = app()->make('translation');
        $language = $this->argument('language') ?: false;

        try {
            // if we have a language, pass it in, if not the method will
            // automagically sync all languages
            $translation->saveMissingTranslations($language);
            return $this->info(__('translation::translation.keys_synced'));
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
