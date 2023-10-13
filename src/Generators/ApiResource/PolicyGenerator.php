<?php

namespace Firevel\Generator\Generators\ApiResource;

use Firevel\ApiResourceGenerator\Resource;
use Firevel\Generator\Generators\BaseGenerator;

class PolicyGenerator extends BaseGenerator
{
    public function handle()
    {
        $resource = $this->resource();

        $path = app_path('Policies') . '/' . $resource->name()->singular()->studly() . "Policy.php";

        $source = $this->render(
            'api-resource/policy',
            [
                'resource' => $resource,
            ]
        );
        $this->createFile($path, $source);
        $this->logger()->info("# Policy created: {$path}");
        $this->logger()->info('- [Optional] Set policies https://laravel.com/docs/authorization#writing-policies');
    }
}
