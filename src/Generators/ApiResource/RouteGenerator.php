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
        $controller = "\\App\\Http\\Controllers\\Api\\{$resource->name()->plural()->studly()}Controller::class";
        $routeCode = "Route::apiResource('{$name}', {$controller});";

        // Check if we're part of a meta-pipeline
        if ($this->context()->isMetaPipeline()) {
            // Collect route data for later consolidation
            $this->context()->push('routes', [
                'name' => $name,
                'controller' => $controller,
                'code' => $routeCode,
                'resourceName' => $resource->name()->plural()->studly(),
            ]);

            $this->logger()->info("# Route collected: {$name}");
            return;
        }

        // Standalone mode - log instructions (current behavior)
        $this->logger()->info("# Generating route");
        $this->logger()->info("- [Required] Register the API route: {$routeCode}");
    }
}
