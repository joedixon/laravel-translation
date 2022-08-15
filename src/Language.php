<?php

namespace JoeDixon\Translation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use JoeDixon\Translation\Database\Factories\LanguageFactory;

class Language extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->connection = config('translation.database.connection');
        $this->table = config('translation.database.languages_table');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(Translation::class);
    }

    protected static function newFactory(): LanguageFactory
    {
        return LanguageFactory::new();
    }
}
