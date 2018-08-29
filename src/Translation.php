<?php

namespace JoeDixon\Translation;

use Illuminate\Database\Eloquent\Model;

class Translation extends Model
{
    protected $guarded = [];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('translation.database.translations_table');
    }

    public function language()
    {
        return $this->belongsTo(Language::class);
    }

    public static function getGroupsForLanguage($language)
    {
        return static::whereHas('language', function ($q) {
            $q->where('language', $language);
        })->select('group')->distinct()->get();
    }
}
