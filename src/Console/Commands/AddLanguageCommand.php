<?php

namespace JoeDixon\Translation\Console\Commands;

class AddLanguageCommand extends BaseCommand
{
    protected $signature = 'translation:add-language';

    protected $description = 'Add a new language to the application';

    public function handle()
    {
        // ask the user for the language they wish to add
        $language = $this->ask(__('translation::translation.prompt_language'));
        $name = $this->ask(__('translation::translation.prompt_name'));

        // attempt to add the key and fail gracefully if exception thrown
        try {
            $this->translation->addLanguage($language, $name);
            $this->info(__('translation::translation.language_added'));
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
