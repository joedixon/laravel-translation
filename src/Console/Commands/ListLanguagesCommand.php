<?php

namespace JoeDixon\Translation\Console\Commands;

use Illuminate\Console\Command;

class ListLanguagesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translation:list-languages';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all of the available languages in the application';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $translation = app()->make('translation');

        $headers = [__('translation::translation.languages')];
        $languages = $translation->allLanguages();

        // map language into array to render one on each line
        $languages = array_map(function ($language) { return [$language]; }, $languages);

        // return a table of results
        $this->table($headers, $languages);
    }
}
