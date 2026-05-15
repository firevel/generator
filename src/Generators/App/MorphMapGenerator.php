<?php

namespace Firevel\Generator\Generators\App;

use Firevel\Generator\Generators\BaseGenerator;
use Illuminate\Support\Str;

class MorphMapGenerator extends BaseGenerator
{
    public static function description(): string
    {
        return 'Generates a MorphMapServiceProvider mapping polymorphic aliases to model FQCNs.';
    }

    public function handle()
    {
        $resources = $this->collectResources();

        if (empty($resources)) {
            return;
        }

        $morphMap = $this->buildMorphMap($resources);

        if (empty($morphMap)) {
            return;
        }

        // Order matters: ensureProviderRegistered() may invoke make:provider, which writes
        // a stub class. writeProvider() then overwrites that stub with our morph-map content.
        $this->ensureProviderRegistered();
        $this->writeProvider($morphMap);
    }

    /**
     * Pull the resources list from the input (or from the current resource if it carries one).
     */
    protected function collectResources(): array
    {
        $input = $this->input();

        if ($input && $input->has('resources')) {
            $resources = $input->get('resources', []);
        } elseif ($this->resource()->has('resources')) {
            $resources = $this->resource()->get('resources', []);
        } else {
            return [];
        }

        return is_array($resources) ? $resources : [];
    }

    /**
     * Build a [alias => FQCN] map from the resource list, deduped and sorted by alias.
     */
    protected function buildMorphMap(array $resources): array
    {
        $map = [];

        foreach ($resources as $resource) {
            if (!is_array($resource) || empty($resource['name'])) {
                continue;
            }

            $name = (string) $resource['name'];
            $singular = Str::singular($name);
            $alias = Str::kebab($singular);
            $class = '\\App\\Models\\' . Str::studly($singular);

            $map[$alias] = $class;
        }

        ksort($map);

        return $map;
    }

    protected function writeProvider(array $morphMap): void
    {
        $source = $this->render(
            'app/morph-map-service-provider',
            ['morphMap' => $morphMap]
        );

        $path = base_path('app/Providers/MorphMapServiceProvider.php');

        $this->createFile($path, $source);

        $this->logger()->info('# Morph map provider written to app/Providers/MorphMapServiceProvider.php (' . count($morphMap) . ' models)');
    }

    /**
     * Ensure MorphMapServiceProvider exists and is registered in bootstrap/providers.php.
     *
     * Delegates to Laravel's `make:provider` Artisan command — in Laravel 11+ that command
     * both creates the provider class and adds it to bootstrap/providers.php for us.
     */
    protected function ensureProviderRegistered(): void
    {
        $providerPath = base_path('app/Providers/MorphMapServiceProvider.php');
        $providersPath = base_path('bootstrap/providers.php');

        $fileExists = file_exists($providerPath);
        $registered = file_exists($providersPath)
            && str_contains(file_get_contents($providersPath), 'MorphMapServiceProvider::class');

        if ($fileExists && $registered) {
            $this->logger()->info('MorphMapServiceProvider already registered — refreshing morph map content');
            return;
        }

        // If only one side is present, drop the stale file so make:provider can recreate
        // both the class and the registration from a clean slate.
        if ($fileExists) {
            if ($this->isDryRun()) {
                $this->logger()->info("- [dry-run] Would delete stale provider: {$providerPath}");
            } else {
                unlink($providerPath);
            }
        }

        $this->artisan('make:provider', ['name' => 'MorphMapServiceProvider']);

        $this->logger()->info('Generated and registered App\\Providers\\MorphMapServiceProvider via make:provider');
    }
}
