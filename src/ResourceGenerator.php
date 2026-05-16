<?php

namespace Firevel\Generator;

use Firevel\Generator\Resource;

class ResourceGenerator
{
    protected $resource;
    protected $generators;
    protected $logger;
    protected $context;

    public function __construct(Resource $resource, array $generators, PipelineContext $context = null)
    {
        $this->resource = $resource;
        $this->generators = $generators;
        $this->context = $context ?? new PipelineContext(false);
    }

    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    public function logger()
    {
        return $this->logger;
    }

    public function generate()
    {
        // Reset per-run summary buckets so each generate() call reports only
        // its own actions, even when a shared context spans multiple pipelines.
        $this->context->forget('summary.actions');
        $this->context->forget('summary.manual_steps');

        foreach ($this->generators as $name => $class) {
            $generatorInstance = new $class($this->resource, $this->context);
            $generatorInstance->setLogger($this->logger());

            try {
                $generatorInstance->handle();
            } catch (\Throwable $e) {
                throw new \RuntimeException(
                    sprintf("Step '%s' (%s) failed: %s", $name, $class, $e->getMessage()),
                    0,
                    $e
                );
            }
        }

        $this->emitSummary();
    }

    /**
     * Print a compact summary block listing file-action counts and any manual
     * follow-up steps recorded during this pipeline run. Designed to be easy
     * for an LLM picking up the transcript to parse without re-scanning.
     */
    protected function emitSummary(): void
    {
        $logger = $this->logger();
        if ($logger === null) {
            return;
        }

        $actions = $this->context->get('summary.actions', []);
        $steps = $this->context->get('summary.manual_steps', []);

        $hasActions = is_array($actions) && !empty($actions);
        $hasSteps = is_array($steps) && !empty($steps);

        if (!$hasActions && !$hasSteps) {
            return;
        }

        $label = $this->summaryLabel();

        $logger->info('');
        $logger->info("── summary{$label} ──");

        if ($hasActions) {
            $parts = [];
            foreach (['created', 'overwrote', 'updated', 'skipped'] as $verb) {
                if (!empty($actions[$verb])) {
                    $parts[] = "{$verb} " . count($actions[$verb]);
                }
            }
            if (!empty($parts)) {
                $logger->info(implode('  ', $parts));
            }
        }

        if ($hasSteps) {
            $logger->info('manual:');
            foreach ($steps as $step) {
                $logger->info("  - {$step}");
            }
        }
    }

    /**
     * Derive a short label for the summary header. Uses the resource name
     * when available so iterated runs (e.g. resources.*) are distinguishable
     * in the transcript.
     */
    protected function summaryLabel(): string
    {
        $name = null;
        if ($this->resource instanceof Resource && $this->resource->has('name')) {
            $name = (string) $this->resource->get('name');
        }

        return $name === null || $name === '' ? '' : " ({$name})";
    }

    public function context()
    {
        return $this->context;
    }
}