<?php

namespace JoeDixon\Translation;

use Illuminate\Database\Eloquent\Model;

class Translation extends Model
{
    protected $guarded = [];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->connection = config('translation.database.connection');
        $this->table = config('translation.database.translations_table');
    }

    public function language()
    {
        return $this->belongsTo(Language::class);
    }

    public static function getGroupsForLanguage($language)
    {
        return static::whereHas('language', function ($q) use ($language) {
            $q->where('language', $language);
        })->whereNotNull('group')
            ->where('group', 'not like', '%single')
            ->select('group')
            ->distinct()
            ->get();
    }
}
