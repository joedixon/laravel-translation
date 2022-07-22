<?php

namespace JoeDixon\Translation\Console\Commands;

class AddTranslationKey extends Command
{
    protected $signature = 'translation:add-translation-key';

    protected $description = 'Add a new language key for the application';

    public function handle(): void
    {
        $language = $this->ask(__('translation::translation.prompt_language_for_key'));

        // we know this should be single or group so we can use the `anticipate`
        // method to give our users a helping hand
        $type = $this->anticipate(__('translation::translation.prompt_type'), ['single', 'group']);

        // if the group type is selected, prompt for the group key
        if ($type === 'group') {
            $file = $this->ask(__('translation::translation.prompt_group'));
        }
        $key = $this->ask(__('translation::translation.prompt_key'));
        $value = $this->ask(__('translation::translation.prompt_value'));

        // attempt to add the key for single or group and fail gracefully if
        // exception is thrown
        if ($type === 'single') {
            try {
                $this->translation->addStringKeyTranslation($language, 'single', $key, $value);

                $this->info(__('translation::translation.language_key_added'));
                return;
            } catch (\Exception $e) {
                $this->error($e->getMessage());
                return;
            }
        } elseif ($type === 'group') {
            try {
                $file = str_replace('.php', '', $file);
                $this->translation->addShortKeyTranslation($language, $file, $key, $value);

                $this->info(__('translation::translation.language_key_added'));
                return;
            } catch (\Exception $e) {
                $this->error($e->getMessage());
                return;
            }
        } else {
            $this->error(__('translation::translation.type_error'));
            return;
        }
    }
}
