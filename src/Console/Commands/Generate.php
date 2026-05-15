<?php

namespace Firevel\Generator\Console\Commands;

use Firevel\Generator\FirevelGeneratorManager;
use Firevel\Generator\PipelineContext;
use Firevel\Generator\Resource;
use Firevel\Generator\ResourceGenerator;
use Firevel\Generator\ScopedPipelineRunner;
use Illuminate\Console\Command;

class Generate extends Command
{
    /**
     * Sentinel value for --json slots meaning "use the previous pipeline's
     * emitted output (via emitOutput) as this pipeline's resource."
     */
    const EMITTED_OUTPUT = '@output';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firevel:generate {pipeline? : Pipeline name or comma-separated list of pipelines} {--only=} {--json= : JSON file/URL or comma-separated list (one per pipeline). Use @output (or an empty slot) to consume the previous pipeline\'s emitted output.} {--pipe : For chained pipelines, auto-fill missing/empty JSON slots after the first with @output. Explicit slots still win.} {--dry-run : Report what each generator would do without writing files or running side effects.} {--skip-existing : When a generated file already exists, skip it instead of overwriting.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a new resource. Supports multiple pipelines and JSON files separated by commas.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $pipelineArg = $this->argument('pipeline');

        // Support multiple pipelines separated by commas
        $pipelineNames = array_map('trim', explode(',', $pipelineArg));

        $manager = app(FirevelGeneratorManager::class);

        // Pre-flight: catch typos in custom pipelines (missing classes, bad
        // scoped references, cycles) before any file is written.
        $registryErrors = $manager->validate();
        if (!empty($registryErrors)) {
            $this->error('Pipeline registry has errors:');
            foreach ($registryErrors as $error) {
                $this->line("  - {$error}");
            }
            return self::FAILURE;
        }

        $pipelines = $manager->getPipelines();

        // Validate all requested pipelines exist before executing any
        foreach ($pipelineNames as $pipelineName) {
            if (empty($pipelines[$pipelineName])) {
                $this->error("Pipeline '{$pipelineName}' is not configured.");
                return;
            }
        }

        // Parse JSON files (support multiple files separated by commas).
        // Preserve empty slots so they can mean "use previous output."
        $jsonSlots = [];
        if (!is_null($this->option('json')) && $this->option('json') !== '') {
            $jsonSlots = array_map('trim', explode(',', $this->option('json')));
        }

        // One shared context for the entire chain. Generators in later pipelines
        // can read state pushed by earlier ones (composer_requires, routes,
        // emitOutput, etc.).
        $chainContext = new PipelineContext(true);
        $chainContext->set('dry_run', (bool) $this->option('dry-run'));
        $chainContext->set('skip_existing', (bool) $this->option('skip-existing'));

        if ($chainContext->get('dry_run')) {
            $this->warn('Dry-run mode: no files will be written and side effects (artisan calls, composer require) will be skipped.');
        }

        $autoPipe = (bool) $this->option('pipe');

        foreach ($pipelineNames as $index => $pipelineName) {
            // Resolve which JSON slot applies to this pipeline. With --pipe set,
            // a missing/empty slot for any pipeline after the first becomes
            // @output (in-memory pipe). Without --pipe, a single shared --json
            // value falls back to "reuse for all pipelines" for compatibility.
            if (array_key_exists($index, $jsonSlots)) {
                $slot = $jsonSlots[$index];
            } elseif ($autoPipe && $index > 0) {
                $slot = self::EMITTED_OUTPUT;
            } elseif (count($jsonSlots) === 1) {
                $slot = $jsonSlots[0];
            } else {
                $slot = null;
            }

            try {
                $attributes = $this->resolveAttributes($slot, $pipelineName, $chainContext);
            } catch (\RuntimeException $e) {
                $this->error($e->getMessage());
                return;
            }

            $resource = new Resource($attributes);

            // Drop any previous-pipeline output so this pipeline starts from a
            // clean output slot. Generators emit theirs via emitOutput().
            $chainContext->forget('output');

            try {
                $this->executePipeline($pipelineName, $pipelines[$pipelineName], $resource, $pipelines, $chainContext);
            } catch (\Throwable $e) {
                // Split into two lines so Symfony's error block doesn't wrap
                // the failing step name onto a separate visual line.
                $this->error("Pipeline '{$pipelineName}' failed.");
                $this->line($e->getMessage());

                $root = $this->rootCause($e);
                if ($root !== $e) {
                    $this->line("  at " . $root->getFile() . ':' . $root->getLine());
                }

                // Abort the rest of the chain — later pipelines may consume
                // output from a pipeline that didn't finish, which is unsafe.
                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }

    /**
     * Walk the exception chain to the deepest cause so we can report the
     * original file:line, not the wrapping RuntimeException's.
     */
    protected function rootCause(\Throwable $e): \Throwable
    {
        while ($e->getPrevious() !== null) {
            $e = $e->getPrevious();
        }
        return $e;
    }

    /**
     * Resolve the attributes array for a pipeline slot. Returns previous
     * pipeline output when the slot is `@previous` or empty; otherwise loads
     * the JSON file or URL.
     *
     * @throws \RuntimeException if the slot is unresolvable.
     */
    protected function resolveAttributes(?string $slot, string $pipelineName, PipelineContext $context): array
    {
        $wantsEmittedOutput = $slot === self::EMITTED_OUTPUT || $slot === '';

        if ($wantsEmittedOutput) {
            if (!$context->has('output')) {
                throw new \RuntimeException(
                    "Pipeline '{$pipelineName}' requested emitted output (via @output or empty slot), "
                    . "but no previous pipeline emitted any. Make sure an earlier pipeline calls emitOutput()."
                );
            }

            $output = $context->get('output');
            return is_array($output) ? $output : [];
        }

        if ($slot === null) {
            return [];
        }

        // Validate local JSON file exists right before loading (skip URLs).
        $isUrl = filter_var($slot, FILTER_VALIDATE_URL) !== false;
        if (!$isUrl && !file_exists($slot)) {
            throw new \RuntimeException("JSON file '{$slot}' not found for pipeline '{$pipelineName}'.");
        }

        return json_decode(file_get_contents($slot), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Execute a single pipeline
     *
     * @param string $pipelineName
     * @param array $pipelineConfig
     * @param Resource $resource
     * @param array $allPipelines
     * @param PipelineContext $context
     * @return void
     */
    protected function executePipeline(string $pipelineName, array $pipelineConfig, Resource $resource, array $allPipelines, PipelineContext $context): void
    {
        // Check if this is a scoped pipeline (meta-pipeline)
        if ($this->isScopedPipeline($pipelineConfig)) {
            $runner = new ScopedPipelineRunner($resource, $pipelineConfig, $allPipelines, $context);
            $runner->setLogger($this);

            if (!empty($this->option('only'))) {
                $runner->setOnly(explode(',', $this->option('only')));
            }

            $runner->execute();
            return;
        }

        // Regular pipeline execution
        $generators = $pipelineConfig;

        if (!empty($this->option('only'))) {
            $only = explode(',', $this->option('only'));
            $generators = array_intersect_key($generators, array_fill_keys($only, ''));
        }

        $generator = new ResourceGenerator($resource, $generators, $context);
        $generator->setLogger($this);
        $generator->generate();
    }

    /**
     * Check if a pipeline configuration contains scoped steps
     *
     * @param array $pipelineConfig
     * @return bool
     */
    protected function isScopedPipeline(array $pipelineConfig): bool
    {
        // Check if the first element is an array with 'scope' and 'pipeline' keys
        $firstElement = reset($pipelineConfig);

        return is_array($firstElement)
            && isset($firstElement['scope'])
            && isset($firstElement['pipeline']);
    }
}
