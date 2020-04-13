<?php

namespace JoeDixon\Translation\Events;

use Illuminate\Foundation\Events\Dispatchable;

class TranslationAdded
{
    use Dispatchable;

    public $key;
    public $group;
    public $value;
    public $language;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(string $language, string $group, string $key, string $value)
    {
        $this->language = $language;
        $this->group = $group;
        $this->key = $key;
        $this->value = $value;
    }
}
