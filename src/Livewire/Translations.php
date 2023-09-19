<?php

namespace JoeDixon\Translation\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use JoeDixon\TranslationCore\TranslationManager;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

class Translations extends Component
{
    #[Url]
    public string $type = 'short';

    #[Url]
    public $language;

    #[Url]
    public $query;

    public Collection $languages;

    /**
     * Mount the component.
     */
    public function mount(TranslationManager $translation): void
    {
        $this->languages = $translation->languages();
        $this->language = $this->language ?: $this->languages->first();
    }

    /**
     * Render the component.
     */
    #[Layout('translation::layout')]
    public function render(): View
    {
        return view('translation::livewire.translations');
    }

    #[Computed]
    public function translations() {
        $translation = app(TranslationManager::class);

        $translations = $translation
            ->keys()
            ->merge($translation->allTranslationsFor($this->language))
            ->search($this->query);

        return $this->type === 'short' ? $translations->shortByGroup() : $translations->string();
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

    #[On('language-added')]
    public function updateLanguages(): void
    {
        $this->languages = app(TranslationManager::class)->languages();
    }
}
