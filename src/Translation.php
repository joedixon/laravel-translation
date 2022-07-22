<?php

namespace JoeDixon\Translation;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use JoeDixon\Translation\Database\Factories\TranslationFactory;

class Translation extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->connection = config('translation.database.connection');
        $this->table = config('translation.database.translations_table');
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    /**
     * @param string $language 
     * @return Collection<int,Translation>
     */
    public static function getGroupsForLanguage(string $language): Collection
    {
        return static
            ::whereHas('language', fn (Builder $q) => $q->where('language', $language))
            ->whereNotNull('group')
            ->where('group', 'not like', '%single')
            ->select('group')
            ->distinct()
            ->get();
    }

    protected static function newFactory(): TranslationFactory
    {
        return TranslationFactory::new();
    }
}
