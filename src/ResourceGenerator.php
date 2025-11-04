<?php

namespace Firevel\Generator;

use Firevel\Generator\Resource;

class ResourceGenerator
{
    protected $resource;
    protected $generators;
    protected $logger;
    protected $context;

    public function __construct(Resource $resource, array $generators, PipelineContext $context = null)
    {
        $this->resource = $resource;
        $this->generators = $generators;
        $this->context = $context ?? new PipelineContext(false);
    }

    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    public function logger()
    {
        return $this->logger;
    }

    public function generate()
    {
        foreach ($this->generators as $name => $class) {
            $generatorInstance = new $class($this->resource, $this->context);
            $generatorInstance->setLogger($this->logger());
            $generatorInstance->handle();
            $this->logger()->info('');
        }
    }

    public function context()
    {
        return $this->context;
    }
}