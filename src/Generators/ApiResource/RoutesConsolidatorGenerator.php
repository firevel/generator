<?php

namespace Firevel\Generator\Generators\ApiResource;

use Firevel\Generator\Generators\BaseGenerator;

class RoutesConsolidatorGenerator extends BaseGenerator
{
    public static function description(): string
    {
        return 'Writes routes collected by RouteGenerator into routes/api.php.';
    }

    public function handle()
    {
        $routes = $this->context()->get('routes', []);

        if (empty($routes)) {
            return;
        }

        $routesFilePath = base_path('routes/api.php');

        if (file_exists($routesFilePath) && $this->hasActualCode($routesFilePath)) {
            // File has content — can't safely overwrite, surface as manual step.
            if (count($routes) === 1) {
                $snippet = $routes[0]['code'];
            } else {
                $lines = ["Route::apiResources(["];
                foreach ($routes as $route) {
                    $lines[] = "    '{$route['name']}' => {$route['controller']},";
                }
                $lines[] = "]);";
                $snippet = implode("\n", $lines);
            }

            $this->addManualStep("add to routes/api.php (file already has content):\n{$snippet}");
            return;
        }

        $content = $this->render('api-resource/routes', ['routes' => $routes]);
        $this->createFile($routesFilePath, $content);
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
