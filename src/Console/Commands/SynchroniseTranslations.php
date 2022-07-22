<?php

namespace JoeDixon\Translation\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use JoeDixon\Translation\Drivers\Database\Database;
use JoeDixon\Translation\Drivers\File\File;
use JoeDixon\Translation\Drivers\Translation;
use JoeDixon\Translation\Exceptions\DriverNotFoundException;
use JoeDixon\Translation\Scanner;
use JoeDixon\Translation\Types\DriverType;
use Throwable;
use UnexpectedValueException;

class SynchroniseTranslations extends Command
{
    protected $signature = 'translation:sync-translations {from?} {to?} {language?}';

    protected $description = 'Synchronise translations between drivers';

    private Translation $fromDriver;

    private Translation $toDriver;

    public function __construct(private Scanner $scanner, private Translation $translation)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $languages = $this->translation->allLanguages()->keys()->toArray();

        try {
            $this->fromDriver = $this->loadDriverFromArgumentOrInput('from');
            $this->toDriver   = $this->loadDriverFromArgumentOrInput('to');
        } catch (DriverNotFoundException $e) {
            $this->error(__('translation::translation.invalid_driver'));
            return;
        }

        try {
            $language = $this->stringArgumentOrInputFromList(
                'language',
                __('translation::translation.prompt_language_if_any'),
                $languages + ['all']
            );
        } catch (Throwable) {
            $this->error(__('translation::translation.invalid_language'));
            return;
        }

        $this->line(__('translation::translation.syncing'));

        // If a specific language is set.
        if ($language !== 'all') {
            $this->mergeTranslations($this->toDriver, $language, $this->fromDriver->allTranslationsFor($language));
        } // Else process all languages.
        else {
            $this->mergeLanguages($this->toDriver, $this->fromDriver->allTranslations());
        }

        $this->info(__('translation::translation.synced'));
    }

    private function stringArgumentOrInputFromList(string $key, string $translation, array $allowed): string
    {
        try {
            $value = $this->stringArgument($key);
            if (!in_array($value, $allowed)) {
                throw new UnexpectedValueException();
            }
        } catch (Throwable) {
            $value = $this->anticipate($translation, $allowed);

            if (!in_array($value, $allowed)) {
                throw new UnexpectedValueException();
            }
        }

        return $value;
    }

    private function loadDriverFromArgumentOrInput(string $which): Translation
    {
        try {
            $driver = $this->stringArgumentOrInputFromList(
                $which,
                __('translation::translation.prompt_' . $which . '_driver'),
                DriverType::values()
            );
        } catch (Throwable) {
            throw new DriverNotFoundException();
        }

        // Create the driver.
        return $this->createDriver($driver);
    }

    private function stringArgument(string $key): string
    {
        $value = $this->argument($key);

        if (! is_string($value)) {
            $type = gettype($value);
            throw new UnexpectedValueException("Argument has to be string, $type provided.");
        }

        return $value;
    }

    private function createDriver(string $driver): Translation
    {
        if ($driver === 'file') {
            return new File(new Filesystem, app('path.lang'), config('app.locale'), $this->scanner);
        }

        return new Database(config('app.locale'), $this->scanner);
    }

    /**
     * @param Translation $driver 
     * @param Collection<string,Collection> $languages 
     * @return void 
     */
    private function mergeLanguages(Translation $driver, Collection $languages): void
    {
        foreach ($languages as $language => $translations) {
            $this->mergeTranslations($driver, $language, $translations);
        }
    }

    /**
     * @param Translation $driver 
     * @param string $language 
     * @param Collection<string,array> $translations 
     * @return void 
     */
    private function mergeTranslations(Translation $driver, string $language, Collection $translations): void
    {
        $this->mergeGroupTranslations($driver, $language, $translations['group']);
        $this->mergeSingleTranslations($driver, $language, $translations['single']);
    }

    private function mergeGroupTranslations(Translation $driver, string $language, array $groups): void
    {
        foreach ($groups as $group => $translations) {
            foreach ($translations as $key => $value) {
                if (is_array($value)) {
                    continue;
                }
                $driver->addShortKeyTranslation($language, $group, $key, $value);
            }
        }
    }

    private function mergeSingleTranslations(Translation $driver, string $language, array $vendors): void
    {
        foreach ($vendors as $vendor => $translations) {
            foreach ($translations as $key => $value) {
                if (is_array($value)) {
                    continue;
                }
                $driver->addStringKeyTranslation($language, $vendor, $key, $value);
            }
        }
    }
}
