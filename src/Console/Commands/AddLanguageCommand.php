<?php

namespace JoeDixon\Translation\Console\Commands;

use Illuminate\Console\Command;

class AddLanguageCommand extends Command
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
        $translation = app()->make('translation');

        // ask the user for the language they wish to add
        $language = $this->ask(__('translation::translation.prompt_language'));

        // attempt to add the key and fail gracefully if exception thrown
        try {
            $translation->addLanguage($language);
            $this->info(__('translation::translation.language_added'));
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
