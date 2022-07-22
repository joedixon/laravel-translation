<?php

namespace JoeDixon\Translation\Console\Commands;

class SynchroniseMissingTranslationKeys extends Command
{
    protected $signature = 'translation:sync-missing-translation-keys {language?}';

    protected $description = 'Add all of the missing translation keys for all languages or a single language';

    public function handle(): void
    {
        $language = $this->argument('language') ?: null;

        if (! (is_string($language) || is_null($language))) {
            $this->error(__('translation::trnaslation.invalid_language'));
            return;
        }

        try {
            // if we have a language, pass it in, if not the method will
            // automagically sync all languages
            $this->translation->saveMissingTranslations($language);

            $this->info(__('translation::translation.keys_synced'));
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
