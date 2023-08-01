<?php

namespace JoeDixon\Translation\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use JoeDixon\TranslationCore\TranslationManager;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

class Translations extends Component
{
    #[Url]
    public string $type = 'short';

    #[Url]
    public $language;

    public Collection $languages;

    public Collection $shortKeys;

    public Collection $stringKeys;

    public Collection $shortKeyTranslations;

    public Collection $stringKeyTranslations;

    /**
     * Mount the component.
     */
    public function mount(TranslationManager $translation): void
    {
        $this->languages = $translation->languages();
        $this->language = $this->language ?: $this->languages->first();

        $keys = $translation->keys();
        $this->shortKeys = $keys->shortByGroup();
        $this->stringKeys = $keys->string();

        $this->setTranslations();
    }

    /**
     * Render the component.
     */
    #[Layout('translation::layout')]
    public function render(): View
    {
        return view('translation::livewire.translations');
    }

    /**
     * Update the translations for the selected langugage.
     */
    public function changeLanguage(TranslationManager $translation): void
    {
        $translations = $translation->allTranslationsFor($this->language);
        $this->shortKeyTranslations = $translations->shortByGroup();
        $this->stringKeyTranslations = $translations->string();
    }

    /**
     * Update a short key translation.
     */
    public function translateShortKey(TranslationManager $translation, string $group, string $key, ?string $value, string $vendor = null): void
    {
        $translation->addShortKeyTranslation($this->language, $group, $key, $value, $vendor);
    }

    /**
     * Update a string key translation.
     */
    public function translateStringKey(TranslationManager $translation, string $key, string $value, string $vendor = null): void
    {
        $translation->addStringKeyTranslation($this->language, $key, $value, $vendor);
    }

    /**
     * Set the translations for the selected language.
     */
    protected function setTranslations(): void
    {
        $translations = app(TranslationManager::class)->allTranslationsFor($this->language);
        $this->shortKeyTranslations = $translations->shortByGroup();
        $this->stringKeyTranslations = $translations->string();
    }
}
