<?php

namespace JoeDixon\Translation\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use JoeDixon\Translation\Drivers\Database;
use JoeDixon\Translation\Drivers\File;
use JoeDixon\Translation\Drivers\Translation;
use JoeDixon\Translation\Scanner;

class SynchroniseTranslationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translation:sync-translations {from?} {to?} {language?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronise translations between drivers';

    /**
     * File scanner.
     *
     * @var Scanner
     */
    private $scanner;

    /**
     * Translation.
     *
     * @var Translation
     */
    private $translation;

    /**
     * From driver.
     */
    private $fromDriver;

    /**
     * To driver.
     */
    private $toDriver;

    /**
     * Translation drivers.
     *
     * @var array
     */
    private $drivers = ['file', 'database'];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Scanner $scanner, Translation $translation)
    {
        parent::__construct();
        $this->scanner = $scanner;
        $this->translation = $translation;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $languages = array_keys($this->translation->allLanguages()->toArray());

        // If a valid from driver has been specified as an argument.
        if ($this->argument('from') && in_array($this->argument('from'), $this->drivers)) {
            $this->fromDriver = $this->argument('from');
        }

        // When the from driver will be entered manually or if the argument is invalid.
        else {
            $this->fromDriver = $this->anticipate(__('translation::translation.prompt_from_driver'), $this->drivers);

            if (! in_array($this->fromDriver, $this->drivers)) {
                return $this->error(__('translation::translation.invalid_driver'));
            }
        }

        // Create the driver.
        $this->fromDriver = $this->createDriver($this->fromDriver);

        // When the to driver has been specified.
        if ($this->argument('to') && in_array($this->argument('to'), $this->drivers)) {
            $this->toDriver = $this->argument('to');
        }

        // When the to driver will be entered manually.
        else {
            $this->toDriver = $this->anticipate(__('translation::translation.prompt_to_driver'), $this->drivers);

            if (! in_array($this->toDriver, $this->drivers)) {
                return $this->error(__('translation::translation.invalid_driver'));
            }
        }

        // Create the driver.
        $this->toDriver = $this->createDriver($this->toDriver);

        // If the language argument is set.
        if ($this->argument('language')) {

            // If all languages should be synced.
            if ($this->argument('language') == 'all') {
                $language = false;
            }
            // When a specific language is set and is valid.
            elseif (in_array($this->argument('language'), $languages)) {
                $language = $this->argument('language');
            } else {
                return $this->error(__('translation::translation.invalid_language'));
            }
        } // When the language will be entered manually or if the argument is invalid.
        else {
            $language = $this->anticipate(__('translation::translation.prompt_language_if_any'), $languages);

            if ($language && ! in_array($language, $languages)) {
                return $this->error(__('translation::translation.invalid_language'));
            }
        }

        $this->line(__('translation::translation.syncing'));

        // If a specific language is set.
        if ($language) {
            $this->mergeTranslations($this->toDriver, $language, $this->fromDriver->allTranslationsFor($language));
        } // Else process all languages.
        else {
            $translations = $this->mergeLanguages($this->toDriver, $this->fromDriver->allTranslations());
        }

        $this->info(__('translation::translation.synced'));
    }

    private function createDriver($driver)
    {
        if ($driver === 'file') {
            return new File(new Filesystem, app('path.lang'), config('app.locale'), $this->scanner);
        }

        return new Database(config('app.locale'), $this->scanner);
    }

    private function mergeLanguages($driver, $languages)
    {
        foreach ($languages as $language => $translations) {
            $this->mergeTranslations($driver, $language, $translations);
        }
    }

    private function mergeTranslations($driver, $language, $translations)
    {
        $this->mergeGroupTranslations($driver, $language, $translations['group']);
        $this->mergeSingleTranslations($driver, $language, $translations['single']);
    }

    private function mergeGroupTranslations($driver, $language, $groups)
    {
        foreach ($groups as $group => $translations) {
            foreach ($translations as $key => $value) {
                if (is_array($value)) {
                    continue;
                }
                $driver->addGroupTranslation($language, $group, $key, $value);
            }
        }
    }

    private function mergeSingleTranslations($driver, $language, $vendors)
    {
        foreach ($vendors as $vendor => $translations) {
            foreach ($translations as $key => $value) {
                if (is_array($value)) {
                    continue;
                }
                $driver->addSingleTranslation($language, $vendor, $key, $value);
            }
        }
    }
}
