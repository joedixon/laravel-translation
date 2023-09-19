<?php

namespace JoeDixon\Translation\Livewire;

use Illuminate\Contracts\View\View;
use JoeDixon\TranslationCore\TranslationManager;
use Livewire\Component;

class AddTranslation extends Component
{
    public $language;
    
    public $type = 'short';

    public $key;

    public $value;

    public $group;

    public $vendor;

    public function mount($language)
    {
        $this->language = $language;
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('translation::livewire.add-translation');
    }

    /**
     * Add a new language.
     */
    public function add(): void
    {
        $translation = app(TranslationManager::class);

        if ($this->type == 'short') {
            $translation->addShortKeyTranslation(
                $this->language, 
                $this->group, 
                $this->key, 
                $this->value, 
                $this->vendor
            );
        } else {
            $translation->addStringKeyTranslation(
                $this->language, 
                $this->key, 
                $this->value, 
                $this->vendor
            );
        }

        $this->dispatch('translation-added'); 
    }
}