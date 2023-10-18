<?php

namespace Firevel\Generator\Generators\ApiResource;

use Firevel\ApiResourceGenerator\Resource;
use Firevel\Generator\Generators\BaseGenerator;

class TransformerGenerator extends BaseGenerator
{
    public function handle()
    {
        $resource = $this->resource();
        $name = $resource->name()->singular()->studly() . 'Transformer';

        $source = $this->render(
            'api-resource/transformer',
            [
                'resource' => $resource,
            ]
        );

        $path = app_path('Transformers') . '/' . "{$name}.php";

        $this->createFile($path, $source);

        $this->logger()->info("# Transformer created: {$name}");
        $this->logger()->info('- [Optional] Set transformer fields');
        $this->logger()->info('- [Optional] Set $availableIncludes fields and relationships');
        $this->logger()->info('  - Documentation https://fractal.thephpleague.com/transformers/#including-data');
    }
}