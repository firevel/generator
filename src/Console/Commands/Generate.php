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
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firevel:generate {pipeline? : Pipeline name or comma-separated list of pipelines} {--only=} {--json= : JSON file or comma-separated list of JSON files (one per pipeline)}';

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

        $pipelines = app(FirevelGeneratorManager::class)->getPipelines();

        // Validate all pipelines exist before executing any
        foreach ($pipelineNames as $pipelineName) {
            if (empty($pipelines[$pipelineName])) {
                $this->error("Pipeline '{$pipelineName}' is not configured.");
                return;
            }
        }

        // Parse JSON files (support multiple files separated by commas)
        $jsonFiles = [];
        if (!empty($this->option('json'))) {
            $jsonFiles = array_map('trim', explode(',', $this->option('json')));
        }

        // Execute each pipeline in sequence with its corresponding JSON
        foreach ($pipelineNames as $index => $pipelineName) {
            // Determine which JSON file to use for this pipeline
            // If there's a corresponding JSON file at the same index, use it
            // Otherwise, use the last JSON file (or empty if no JSON files)
            $jsonFile = null;
            if (!empty($jsonFiles)) {
                $jsonFile = $jsonFiles[$index] ?? end($jsonFiles);
            }

            // Load attributes from JSON file
            $attributes = [];
            if ($jsonFile) {
                // Validate local JSON file exists right before loading (skip URLs)
                $isUrl = filter_var($jsonFile, FILTER_VALIDATE_URL) !== false;
                if (!$isUrl && !file_exists($jsonFile)) {
                    $this->error("JSON file '{$jsonFile}' not found for pipeline '{$pipelineName}'.");
                    return;
                }

                $attributes = json_decode(file_get_contents($jsonFile), true, 512, JSON_THROW_ON_ERROR);
            }

            $resource = new Resource($attributes);

            $this->executePipeline($pipelineName, $pipelines[$pipelineName], $resource, $pipelines);
        }
    }

    /**
     * Execute a single pipeline
     *
     * @param string $pipelineName
     * @param array $pipelineConfig
     * @param Resource $resource
     * @param array $allPipelines
     * @return void
     */
    protected function executePipeline(string $pipelineName, array $pipelineConfig, Resource $resource, array $allPipelines): void
    {
        // Check if this is a scoped pipeline (meta-pipeline)
        if ($this->isScopedPipeline($pipelineConfig)) {
            // Execute scoped pipeline
            $context = new PipelineContext(true);
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

        $generator = new ResourceGenerator($resource, $generators);
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
