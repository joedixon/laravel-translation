<?php

namespace JoeDixon\Translation\Events;

use Illuminate\Foundation\Events\Dispatchable;

class TranslationUpdated
{
    use Dispatchable;

    public $key;
    public $value;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(string $key, string $value)
    {
        $this->key = $key;
        $this->value = $value;
    }
}
