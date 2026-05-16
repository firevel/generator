<?php

namespace Firevel\Generator\Generators\ApiResource;

use Firevel\ApiResourceGenerator\Resource;
use Firevel\Generator\Generators\BaseGenerator;

class RouteGenerator extends BaseGenerator
{
    public static function description(): string
    {
        return 'Builds the apiResource route definition and pushes it into the pipeline context.';
    }

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

            $this->logger()->info("collected route '{$name}' (consolidated later)");
            return;
        }

        // Standalone mode — surface the route as a manual follow-up so it
        // ends up in the per-pipeline summary instead of getting buried.
        $this->addManualStep("register route in routes/api.php: {$routeCode}");
    }
}
