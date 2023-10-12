<?php

namespace Firevel\Generator\Console\Commands;

use Firevel\Generator\Resource;
use Firevel\Generator\ResourceGenerator;
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

        $pipelines = config('generator.pipelines');

        if (empty($pipelines[$pipeline])) {
            $this->error("Pipeline '{$pipeline}' is not configured.");
            return;
        }

        if (!empty($this->option('json'))) {
            $attributes = json_decode(file_get_contents($this->option('json')), true);
        }

        $resource = new Resource($attributes);
        $geneators = $pipelines[$pipeline];

        if (!empty($this->option('only'))) {
            $only = explode(',', $this->option('only'));
            $geneators = array_intersect_key($geneators, array_fill_keys($only, ''));
        }

        $geneator = new ResourceGenerator($resource, $geneators);
        $geneator->setLogger($this);
        $geneator->generate();
    }
}
