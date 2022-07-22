<?php

namespace JoeDixon\Translation\Events;

use Illuminate\Foundation\Events\Dispatchable;

class TranslationAdded
{
    use Dispatchable;

    public function __construct(
        public string $language,
        public string $group,
        public string $key,
        public string $value,
    ) {
    }
}
