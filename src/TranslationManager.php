<?php

namespace JoeDixon\Translation;

use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;
use JoeDixon\Translation\Drivers\File;
use JoeDixon\Translation\Drivers\Database;

class TranslationManager
{
    private $app;

    private $config;

    private $scanner;

    public function __construct($app, $config, $scanner)
    {
        $this->app = $app;
        $this->config = $config;
        $this->scanner = $scanner;
    }

    public function resolve()
    {
        $driver = explode(':', $this->config['driver'])[0] ?? 'file';
        $driverResolver = Str::studly($driver);
        $method = "resolve{$driverResolver}Driver";

        if (! method_exists($this, $method)) {
            throw new \InvalidArgumentException("Invalid driver [$driver]");
        }

        return $this->{$method}();
    }

    protected function resolveFileDriver()
    {
        return new File(new Filesystem(), $this->app['path.lang']);
    }

    protected function resolveDatabaseDriver()
    {
        return new Database();
    }
}
