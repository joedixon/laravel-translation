<?php

namespace JoeDixon\Translation\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use JoeDixon\Translation\Language;

class LanguageFactory extends Factory
{
    protected $model = Language::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'language' => fake()->word,
            'name' => fake()->word,
        ];
    }
}
