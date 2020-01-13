<?php

namespace JoeDixon\Translation\Console\Commands;

use Illuminate\Console\Command;
use JoeDixon\Translation\Drivers\DriverInterface;

class BaseCommand extends Command
{
    protected $translation;

    public function __construct(DriverInterface $translation)
    {
        parent::__construct();
        $this->translation = $translation;
    }
}
