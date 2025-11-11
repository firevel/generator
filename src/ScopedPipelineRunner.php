<?php

namespace Firevel\Generator;

use Firevel\Generator\Resource;
use Firevel\Generator\ResourceGenerator;
use Firevel\Generator\PipelineContext;

class ScopedPipelineRunner
{
    protected $resource;
    protected $scopedSteps;
    protected $allPipelines;
    protected $logger;
    protected $context;

    public function __construct(Resource $resource, array $scopedSteps, array $allPipelines, PipelineContext $context = null)
    {
        $this->resource = $resource;
        $this->scopedSteps = $scopedSteps;
        $this->allPipelines = $allPipelines;
        $this->context = $context ?? new PipelineContext(true);
    }

    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    public function logger()
    {
        return $this->logger;
    }

    /**
     * Execute all scoped pipeline steps
     */
    public function execute()
    {
        // Store the full input resource in context for access from generators
        $this->context->set('input', $this->resource);

        foreach ($this->scopedSteps as $step) {
            $this->executeStep($step);
        }
    }

    /**
     * Execute a single scoped pipeline step
     *
     * @param array $step
     */
    protected function executeStep(array $step)
    {
        $scope = $step['scope'] ?? '';
        $pipelineName = $step['pipeline'] ?? null;

        if (empty($pipelineName)) {
            $this->logger()->error("Pipeline name is required for scoped step");
            return;
        }

        if (!isset($this->allPipelines[$pipelineName])) {
            $this->logger()->error("Pipeline '{$pipelineName}' not found");
            return;
        }

        $generators = $this->allPipelines[$pipelineName];

        // Check if scope uses iterator syntax (e.g., 'resources.*')
        if (str_ends_with($scope, '.*')) {
            $this->executeIteratedScope($scope, $generators);
        } else {
            $this->executeSingleScope($scope, $generators);
        }
    }

    /**
     * Execute pipeline for a single scope
     *
     * @param string $scope
     * @param array $generators
     */
    protected function executeSingleScope(string $scope, array $generators)
    {
        // Resolve scope data
        $scopedData = $this->resolveScope($scope);

        if ($scopedData === null) {
            $this->logger()->warn("Scope '{$scope}' not found in resource data");
            return;
        }

        // Create resource with scoped data
        $scopedResource = new Resource(is_array($scopedData) ? $scopedData : []);

        // Execute pipeline with scoped resource
        $generator = new ResourceGenerator($scopedResource, $generators, $this->context);
        $generator->setLogger($this->logger());
        $generator->generate();
    }

    /**
     * Execute pipeline for each item in an iterated scope
     *
     * @param string $scope
     * @param array $generators
     */
    protected function executeIteratedScope(string $scope, array $generators)
    {
        // Remove '.*' suffix to get collection path
        $collectionPath = substr($scope, 0, -2);

        // Resolve collection
        $collection = $this->resolveScope($collectionPath);

        if (!is_array($collection)) {
            $this->logger()->warn("Scope '{$collectionPath}' is not iterable");
            return;
        }

        // Execute pipeline for each item
        foreach ($collection as $index => $item) {
            $this->logger()->info("");
            $this->logger()->info("=== Processing {$collectionPath}[{$index}] ===");
            $this->logger()->info("");

            // Create resource with item data
            $itemResource = new Resource(is_array($item) ? $item : []);

            // Execute pipeline
            $generator = new ResourceGenerator($itemResource, $generators, $this->context);
            $generator->setLogger($this->logger());
            $generator->generate();
        }
    }

    /**
     * Resolve scope path from resource
     *
     * @param string $scope
     * @return mixed
     */
    protected function resolveScope(string $scope)
    {
        // Empty scope or '.' means use entire resource
        if (empty($scope) || $scope === '.') {
            return $this->resource->all();
        }

        // Use dot notation to get nested data
        if ($this->resource->has($scope)) {
            return $this->resource->get($scope);
        }

        return null;
    }
}
