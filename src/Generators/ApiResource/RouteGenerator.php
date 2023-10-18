<?php

namespace Firevel\Generator\Generators\ApiResource;

use Firevel\ApiResourceGenerator\Resource;
use Firevel\Generator\Generators\BaseGenerator;

class RouteGenerator extends BaseGenerator
{
    public function handle()
    {
        $resource = $this->resource();
        $name = str_replace('_', '-', $resource->name()->plural()->snake());
        $this->logger()->info("# Generating route");
        $this->logger()->info("- [Required] Register the API route: Route::apiResource('{$name}', \\App\\Http\\Controllers\\Api\\{$resource->name()->plural()->studly()}Controller::class);");
    }
}
