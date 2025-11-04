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
    protected $signature = 'firevel:generate {pipeline?} {--only=} {--json=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a new resource.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $attributes = [];
        $pipeline = $this->argument('pipeline');

        $pipelines = app(FirevelGeneratorManager::class)->getPipelines();

        if (empty($pipelines[$pipeline])) {
            $this->error("Pipeline '{$pipeline}' is not configured.");
            return;
        }

        if (!empty($this->option('json'))) {
            $attributes = json_decode(file_get_contents($this->option('json')), true, 512, JSON_THROW_ON_ERROR);
        }

        $resource = new Resource($attributes);
        $pipelineConfig = $pipelines[$pipeline];

        // Check if this is a scoped pipeline (meta-pipeline)
        if ($this->isScopedPipeline($pipelineConfig)) {
            // Execute scoped pipeline
            $context = new PipelineContext(true);
            $runner = new ScopedPipelineRunner($resource, $pipelineConfig, $pipelines, $context);
            $runner->setLogger($this);
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
