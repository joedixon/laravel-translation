<?php

namespace JoeDixon\Translation\Livewire;

use Illuminate\Contracts\View\View;
use JoeDixon\TranslationCore\TranslationManager;
use Livewire\Component;

class AddLanguage extends Component
{
    public $language;

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('translation::livewire.add-language');
    }

    /**
     * Add a new language.
     */
    public function add(): void
    {
        app(TranslationManager::class)
            ->addLanguage($this->language);

        $this->dispatch('language-added'); 
    }
}