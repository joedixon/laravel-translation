<?php

namespace JoeDixon\Translation\Console\Commands;

use Illuminate\Console\Command;
use JoeDixon\Translation\Drivers\Translation;

class BaseCommand extends Command
{
    public function __construct(protected Translation $translation)
    {
        parent::__construct();
    }
}
