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

        // Check if file exists and has actual PHP code
        if (file_exists($routesFilePath) && $this->hasActualCode($routesFilePath)) {
            // File exists with code - log routes to add manually
            $this->logger()->info("# routes/api.php already exists with content");
            $this->logger()->info("# Add the following routes to your routes/api.php file:\n");

            // Show appropriate format based on number of routes
            if (count($routes) === 1) {
                $this->logger()->info($routes[0]['code']);
            } else {
                $this->logger()->info("Route::apiResources([");
                foreach ($routes as $route) {
                    $this->logger()->info("    '{$route['name']}' => {$route['controller']},");
                }
                $this->logger()->info("]);");
            }

            $this->logger()->info("");
            $this->logger()->info("# Don't forget to import the controllers at the top of the file");
        } else {
            // File doesn't exist or is empty - create new file with all routes
            $content = $this->render('api-resource/routes', [
                'routes' => $routes,
            ]);

            $this->createFile($routesFilePath, $content);

            $this->logger()->info("# Created routes/api.php with " . count($routes) . " route(s)");
            $this->logger()->info("- File: " . $routesFilePath);
        }
    }

    /**
     * Check if the file has actual PHP code (not just empty or whitespace)
     *
     * @param string $filePath
     * @return bool
     */
    protected function hasActualCode(string $filePath): bool
    {
        $content = file_get_contents($filePath);

        // Remove PHP opening/closing tags
        $content = preg_replace('/<\?php/', '', $content);
        $content = preg_replace('/\?>/', '', $content);

        // Remove single-line comments
        $content = preg_replace('/\/\/.*$/m', '', $content);

        // Remove multi-line comments
        $content = preg_replace('/\/\*.*?\*\//s', '', $content);

        // Remove use statements (imports are not considered "actual code")
        $content = preg_replace('/^\s*use\s+[^;]+;/m', '', $content);

        // Trim whitespace
        $content = trim($content);

        // If there's anything left, consider it actual code
        return !empty($content);
    }
}
