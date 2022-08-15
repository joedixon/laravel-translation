<?php

namespace JoeDixon\Translation\Drivers;

use Closure;
use Illuminate\Support\Collection;

class CombinedTranslations
{
    /**
     * @param  Collection<string,Collection<string,array|string>>  $stringKeyTranslations
     * @param  Collection<string,Collection<string,array|string>>  $shortKeyTranslations
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
        $this->shortKeyTranslations = $this->shortKeyTranslations->map($callback);

        return $this;
    }

    public static function make(?Collection $stringKeyTranslations = null, ?Collection $shortKeyTranslations = null): self
    {
        return new static(
            $stringKeyTranslations ?? new Collection(),
            $shortKeyTranslations ?? new Collection(),
        );
    }

    public function emptyValues()
    {
        $this->stringKeyTranslations = $this->emptyCollectionValues($this->stringKeyTranslations);
        $this->shortKeyTranslations = $this->emptyCollectionValues($this->shortKeyTranslations);

        return $this;
    }

    public function emptyCollectionValues(Collection $collection): Collection
    {
        return $collection->map(function ($item, $index) {
            if ($item instanceof Collection) {
                return $this->emptyCollectionValues($item);
            }

            if (is_array($item)) {
                return $this->emptyCollectionValues(new Collection($item))->toArray();
            }

            return '';
        });
    }
}
