<?php

namespace Firevel\Generator\Generators\ApiResource;

use Firevel\ApiResourceGenerator\Resource;
use Firevel\Generator\Generators\BaseGenerator;

class FactoryGenerator extends BaseGenerator
{
    public static function description(): string
    {
        return 'Generates a model factory for use in tests and seeders.';
    }

    public function handle()
    {
        $resource = $this->resource();
        $name = $resource->name()->singular()->studly();

        $source = $this->render(
            'api-resource/factory',
            [
                'resource' => $resource,
            ]
        );

        $path = database_path('factories' . '/' . $name . "Factory.php");

        $this->createFile($path, $source);
    }
}