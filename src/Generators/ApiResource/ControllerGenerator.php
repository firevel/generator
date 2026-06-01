<?php

namespace Firevel\Generator\Generators\ApiResource;

use Firevel\ApiResourceGenerator\Resource;
use Firevel\Generator\Generators\BaseGenerator;

class ControllerGenerator extends BaseGenerator
{
    public static function description(): string
    {
        return 'Generates the API controller exposing index/store/show/update/destroy actions.';
    }

    public function handle()
    {
        $this->requirePackage('firevel/api', '^0.1');

        $resource = $this->resource();
        $source = $this->generateSource();

        $path = app_path('Http/Controllers/Api') . '/' . $resource->name()->plural()->studly() . "Controller.php";

        $this->createFile($path, $source);
    }

    public function generateSource()
    {
        return $this->render(
            'api-resource/controller',
            [
                'resource' => $this->resource(),
            ]
        );
    }
}
