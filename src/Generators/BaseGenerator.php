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
        if ($this->isDryRun()) {
            $rendered = trim($command . ' ' . implode(' ', array_map(
                fn($k, $v) => is_int($k) ? (string) $v : "--{$k}=" . escapeshellarg((string) $v),
                array_keys($parameters),
                array_values($parameters),
            )));
            $this->logger?->info("[dry-run] Would call: php artisan {$rendered}");
            return;
        }

        Artisan::call($command, $parameters);
    }

    /**
     * True when --dry-run was passed to firevel:generate. Generators with
     * side effects (file writes, shelling out, artisan calls) should consult
     * this before performing the side effect.
     */
    public function isDryRun(): bool
    {
        return (bool) $this->context->get('dry_run', false);
    }

    /**
     * True when --skip-existing was passed. Used by createFile() to avoid
     * overwriting hand-edited files.
     */
    public function shouldSkipExisting(): bool
    {
        return (bool) $this->context->get('skip_existing', false);
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

    /**
     * Create or overwrite a file at $path with $content.
     *
     * Honors --skip-existing (skips if the file exists) and --dry-run (logs
     * the intent instead of writing). Returns true if the file was written
     * (or would be in dry-run), false if it was skipped.
     */
    protected function createFile($path, $content)
    {
        $exists = file_exists($path);

        if ($exists && $this->shouldSkipExisting()) {
            $this->logger?->info("- Skipped (exists): {$path}");
            return false;
        }

        if ($this->isDryRun()) {
            $verb = $exists ? 'Would overwrite' : 'Would create';
            $this->logger?->info("- [dry-run] {$verb}: {$path} (" . strlen((string) $content) . " bytes)");
            return true;
        }

        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, $content);
        return true;
    }

    /**
     * Update an in-place file (.env, composer.json, etc.) with new contents.
     *
     * Differs from createFile() by not honoring --skip-existing — the whole
     * point of these files is to modify them. Still honors --dry-run.
     */
    protected function updateFile(string $path, string $content): bool
    {
        if ($this->isDryRun()) {
            $this->logger?->info("- [dry-run] Would update: {$path} (" . strlen($content) . " bytes)");
            return true;
        }

        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, $content);
        return true;
    }

    abstract public function handle();
}

