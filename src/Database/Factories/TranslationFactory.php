<?php

namespace JoeDixon\Translation\Database\Factories;

use JoeDixon\Translation\Language;
use Illuminate\Database\Eloquent\Factories\Factory;
use JoeDixon\Translation\Translation;

class TranslationFactory extends Factory
{
    protected $model = Translation::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'language_id' => Language::factory(),
            'group' => fake()->word,
            'key' => fake()->word,
            'value' => fake()->sentence,
        ];
    }

    public function shortKey()
    {
        return $this->state(function () {
            return [
                'group' => fake()->word,
            ];
        });
    }

    public function stringKey()
    {
        return $this->state(function () {
            return [
                'group' => 'string',

            ];
        });
    }
}
