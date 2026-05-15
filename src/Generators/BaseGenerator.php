<?php

namespace Firevel\Generator\Generators;

use Firevel\Generator\PipelineContext;
use Firevel\Generator\Resource;
use Illuminate\Support\Facades\Artisan;

abstract class BaseGenerator
{
    protected $resource;
    protected $logger;
    protected $context;

    public function __construct(Resource $resource, PipelineContext $context = null)
    {
        $this->resource = $resource;
        $this->context = $context ?? new PipelineContext(false);
    }

    /**
     * Short human-readable description shown by `firevel:generate:list`.
     * Override in concrete generators.
     */
    public static function description(): string
    {
        return '';
    }

    protected function artisan($command, $parameters = [])
    {
        Artisan::call($command, $parameters);
    }

    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    public function logger()
    {
        return $this->logger;
    }

    public function resource() {
        return $this->resource;
    }

    public function context()
    {
        return $this->context;
    }

    /**
     * Get the full input resource (before any scoping)
     *
     * @return Resource|null
     */
    public function input()
    {
        return $this->context->get('input');
    }

    public function render($stub, $attributes)
    {
        return view('firevel-generator::' . $stub, $attributes)->render();
    }

    /**
     * Expose the result of this pipeline so a chained pipeline can consume it
     * as its input via `--json=@previous`.
     *
     * Stored under `output` in the shared PipelineContext. The Generate command
     * captures and clears this value between pipelines, so only the last value
     * set by a pipeline survives.
     *
     * @param array|\Firevel\Generator\Resource $data
     */
    protected function emitOutput($data): void
    {
        if ($data instanceof Resource) {
            $data = $data->all();
        }

        $this->context->set('output', $data);
    }

    /**
     * Declare that the generated code needs a Composer package.
     *
     * The package + version is pushed into the pipeline context under
     * 'composer_requires'. ComposerRequireGenerator collects these alongside
     * schema-declared requires and writes them to composer.json at the end of
     * the app pipeline.
     *
     * Use '*' to defer the version to an app-level or per-resource require.
     */
    protected function requirePackage(string $name, string $version): void
    {
        $requires = $this->context->get('composer_requires', []);

        if (!is_array($requires)) {
            $requires = [];
        }

        if (isset($requires[$name])) {
            $existing = $requires[$name];

            // Same version is a no-op.
            if ($existing === $version) {
                return;
            }

            // Prefer a concrete version over '*'.
            if ($existing === '*') {
                $requires[$name] = $version;
                $this->context->set('composer_requires', $requires);
                return;
            }

            // Incoming '*' yields to an already-concrete version.
            if ($version === '*') {
                return;
            }

            // Two different concrete versions — keep the first, warn.
            if ($this->logger) {
                $this->logger->warn("Conflicting generator requires for {$name}: '{$existing}' vs '{$version}' — keeping '{$existing}'");
            }
            return;
        }

        $requires[$name] = $version;
        $this->context->set('composer_requires', $requires);
    }

    protected function createFile($path, $content)
    {
        // Get the directory name from the file path
        $dir = dirname($path);

        // Check if the directory exists
        if (!is_dir($dir)) {
            // If the directory does not exist, create it
            mkdir($dir, 0755, true);
        }

        // Create or update the file
        file_put_contents($path, $content);
    }

    abstract public function handle();
}

