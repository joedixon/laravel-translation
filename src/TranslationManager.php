<?php

namespace JoeDixon\Translation;

use Illuminate\Filesystem\Filesystem;
use JoeDixon\Translation\Drivers\File;

class TranslationManager
{
    private $app;

    private $config;

    public function __construct($app, $config)
    {
        $this->app = $app;
        $this->config = $config;
    }

    public function resolve()
    {
        $driver = $this->config['driver'];
        $driverResolver = studly_case($driver);
        $method = "resolve{$driverResolver}Driver";

        if (!method_exists($this, $method)) {
            throw new \InvalidArgumentException("Invalid driver [$driver]");
        }

        return $this->{$method}();
    }

    protected function resolveFileDriver()
    {
        return new File(new Filesystem, $this->app['path.lang'], $this->app->config['app']['locale']);
    }
}
