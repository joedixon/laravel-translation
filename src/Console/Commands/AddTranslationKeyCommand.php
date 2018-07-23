<?php

namespace JoeDixon\Translation\Console\Commands;

use Illuminate\Console\Command;

class AddTranslationKeyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translation:add-translation-key';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add a new language key for the application';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $translation = app()->make('translation');

        $language = $this->ask(__('translation::translation.prompt_language_for_key'));
        $type = $this->anticipate(__('translation::translation.prompt_type'), ['json', 'array']);
        if ($type === 'array') {
            $file = $this->ask(__('translation::translation.prompt_file'));
        }
        $key = $this->ask(__('translation::translation.prompt_key'));
        $value = $this->ask(__('translation::translation.prompt_value'));

        if ($type === 'json') {
            try {
                $translation->addJsonTranslation($language, $key, $value);
                return $this->info(__('translation::translation.language_key_added'));
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
        } elseif ($type === 'array') {
            try {
                $file = str_replace('.php', '', $file);
                $translation->addArrayTranslation($language, "{$file}.{$key}", $value);
                return $this->info(__('translation::translation.language_key_added'));
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {
            return $this->error(__('translation::translation.type_error'));
        }
    }
}
