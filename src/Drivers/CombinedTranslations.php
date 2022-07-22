<?php

namespace JoeDixon\Translation\Drivers;

use Closure;
use Illuminate\Support\Collection;

class CombinedTranslations
{

    /**
     * @param Collection<string,Collection<string,array|string>> $stringKeyTranslations
     * @param Collection<string,Collection<string,array|string>> $shortKeyTranslations
     * @return void
     */
    public function __construct(
        public Collection $stringKeyTranslations,
        public Collection $shortKeyTranslations,
    ) {
    }

    public function toArray(): array
    {
        return [
            'string' => $this->stringKeyTranslations,
            'short' => $this->shortKeyTranslations,
        ];
    }

    public function map(Closure $callback): self
    {
        $this->stringKeyTranslations = $this->stringKeyTranslations->map($callback);
        $this->shortKeyTranslations  = $this->shortKeyTranslations->map($callback);

        return $this;
    }
}
