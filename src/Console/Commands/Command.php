<?php

namespace JoeDixon\Translation\Console\Commands;

use Illuminate\Console\Command as BaseCommand;
use JoeDixon\Translation\Drivers\Translation;

class Command extends BaseCommand
{
    public function __construct(protected Translation $translation)
    {
        parent::__construct();
    }
}
