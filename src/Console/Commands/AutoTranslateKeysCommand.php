<?php

namespace JoeDixon\Translation\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use JoeDixon\Translation\Drivers\Database;
use JoeDixon\Translation\Drivers\File;
use JoeDixon\Translation\Drivers\Translation;
use JoeDixon\Translation\Scanner;

class AutoTranslateKeysCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translation:auto-translate {language?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto translate keys using google translate';

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
            // automagically translate all languages
            $this->translation->autoTranslate($language);

            return $this->info(__('translation::translation.auto_translated'));
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
        
        
    }
}
