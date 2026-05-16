<?php

namespace Firevel\Generator\Generators\ApiResource;

use Firevel\ApiResourceGenerator\Resource;
use Firevel\Generator\Generators\BaseGenerator;

class TransformerGenerator extends BaseGenerator
{
    public static function description(): string
    {
        return 'Generates a Fractal transformer for serializing the resource (requires spatie/laravel-fractal).';
    }

    public function handle()
    {
        $this->requirePackage('spatie/laravel-fractal', '^6.0');

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
    }
}