<?php

namespace JoeDixon\Translation;

use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    protected $guarded = [];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->connection = config('translation.database.connection');
        $this->table = config('translation.database.languages_table');
    }

    public function translations()
    {
        return $this->hasMany(Translation::class);
    }
}
