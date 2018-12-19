<?php

namespace JoeDixon\Translation\Console\Commands;

class SynchroniseMissingTranslationKeys extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translation:sync-missing-translation-keys {language?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add all of the missing translation keys for all languages or a single language';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $language = $this->argument('language') ?: false;

        try {
            // if we have a language, pass it in, if not the method will
            // automagically sync all languages
            $this->translation->saveMissingTranslations($language);

            return $this->info(__('translation::translation.keys_synced'));
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
