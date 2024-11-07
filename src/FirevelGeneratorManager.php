<?php

namespace Firevel\Generator;

class FirevelGeneratorManager
{
    protected $pipelines = [];

    public function extend(string $name, array $pipeline)
    {
        $this->pipelines[$name] = $pipeline;
    }

    public function getPipelines()
    {
        $configPipelines = config('generator.pipelines', []);
        
        return array_merge($configPipelines, $this->pipelines);
    }
}
