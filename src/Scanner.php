<?php

namespace JoeDixon\Translation;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use JoeDixon\Translation\Drivers\CombinedTranslations;

class Scanner
{
    public function __construct(
        private Filesystem $disk,
        private array $scanPaths,
        private array $translationMethods,
    ) {
    }

    /**
     * Scan all the files in the provided $scanPath for translations.
     *
     * @return CombinedTranslations
     */
    public function findTranslations(): CombinedTranslations
    {
        $results = new CombinedTranslations(new Collection(), new Collection());

        // This has been derived from a combination of the following:
        // * Laravel Language Manager GUI from Mohamed Said (https://github.com/themsaid/laravel-langman-gui)
        // * Laravel 5 Translation Manager from Barry vd. Heuvel (https://github.com/barryvdh/laravel-translation-manager)
        $matchingPattern =
            '[^\w]'. // Must not start with any alphanum or _
            '(?<!->)'. // Must not start with ->
            '('.implode('|', $this->translationMethods).')'. // Must start with one of the functions
            "\(". // Match opening parentheses
            "[\'\"]". // Match " or '
            '('. // Start a new group to match:
            '.+'. // Must start with group
            ')'. // Close group
            "[\'\"]". // Closing quote
            "[\),]";  // Close parentheses or new parameter

        foreach ($this->scanPaths as $path) {
            foreach ($this->disk->allFiles($path) as $file) {
                if (preg_match_all("/$matchingPattern/siU", $file->getContents(), $matches)) {
                    foreach ($matches[2] as $key) {
                        if (preg_match("/(^[a-zA-Z0-9:_-]+([.][^\1)\ ]+)+$)/siU", $key, $arrayMatches)) {
                            [$file, $k] = explode('.', $arrayMatches[0], 2);
                            data_set($results->shortKeyTranslations, $file.'.'.$k, '');
                            continue;
                        } else {
                            data_set($results->stringKeyTranslations, 'string.'.$key, '');
                        }
                    }
                }
            }
        }

        return $results;
    }
}
