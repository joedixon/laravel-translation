<?php

namespace JoeDixon\Translation\Console\Commands;

class AddLanguageCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translation:add-language';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add a new language to the application';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
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
