<?php

namespace Firevel\Generator\Generators\ApiResource;

use Firevel\Generator\Generators\BaseGenerator;

class RoutesConsolidatorGenerator extends BaseGenerator
{
    public function handle()
    {
        // Get all collected routes from context
        $routes = $this->context()->get('routes', []);

        if (empty($routes)) {
            $this->logger()->info("# No routes to consolidate");
            return;
        }

        $routesFilePath = base_path('routes/api.php');

        if (file_exists($routesFilePath)) {
            // File exists - log code to add manually
            $this->logger()->info("# routes/api.php already exists");
            $this->logger()->info("# Add the following routes to your routes/api.php file:\n");

            foreach ($routes as $route) {
                $this->logger()->info($route['code']);
            }

            $this->logger()->info("");
            $this->logger()->info("# Don't forget to import the controllers at the top of the file");
        } else {
            // Create new file with all routes
            $content = $this->render('api-resource/routes', [
                'routes' => $routes,
            ]);

            $this->createFile($routesFilePath, $content);

            $this->logger()->info("# Created routes/api.php with " . count($routes) . " route(s)");
            $this->logger()->info("- File: " . $routesFilePath);
        }
    }
}
