<?php

namespace Firevel\Generator\Generators;

use Firevel\Generator\PipelineContext;
use Firevel\Generator\Resource;
use Illuminate\Support\Facades\Artisan;

abstract class BaseGenerator
{
    protected $resource;
    protected $logger;
    protected $context;

    public function __construct(Resource $resource, PipelineContext $context = null)
    {
        $this->resource = $resource;
        $this->context = $context ?? new PipelineContext(false);
    }

    protected function artisan($command, $parameters = [])
    {
        Artisan::call($command, $parameters);
    }

    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    public function logger()
    {
        return $this->logger;
    }

    public function resource() {
        return $this->resource;
    }

    public function context()
    {
        return $this->context;
    }

    public function render($stub, $attributes)
    {
        return view('firevel-generator::' . $stub, $attributes)->render();
    }

    protected function createFile($path, $content)
    {
        // Get the directory name from the file path
        $dir = dirname($path);

        // Check if the directory exists
        if (!is_dir($dir)) {
            // If the directory does not exist, create it
            mkdir($dir, 0755, true);
        }

        // Create or update the file
        file_put_contents($path, $content);
    }

    abstract public function handle();
}

