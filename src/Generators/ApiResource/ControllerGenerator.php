<?php

namespace Firevel\Generator\Generators\ApiResource;

use Firevel\ApiResourceGenerator\Resource;
use Firevel\Generator\Generators\BaseGenerator;

class ControllerGenerator extends BaseGenerator
{
    public function handle()
    {
        $resource = $this->resource();
        $source = $this->render(
            'api-resource/controller',
            [
                'resource' => $resource,
            ]
        );

        $path = app_path('Http/Controllers/Api') . '/' . $resource->name()->plural()->studly() . "Controller.php";

        $this->createFile($path, $source);
        $this->logger()->info("# Controller created: $path");
    }
}
