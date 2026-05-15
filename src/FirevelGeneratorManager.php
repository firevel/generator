<?php

namespace Firevel\Generator;

class FirevelGeneratorManager
{
    protected $pipelines = [];

    /**
     * Register a pipeline at runtime. Accepts either the legacy steps-only array
     * or the hybrid shape `['description' => string, 'steps' => array]`.
     */
    public function extend(string $name, array $pipeline)
    {
        $this->pipelines[$name] = $pipeline;
    }

    /**
     * Return all pipelines as `[name => steps[]]`. Hybrid-shape entries are
     * flattened to their steps so existing callers keep working.
     */
    public function getPipelines()
    {
        $merged = $this->mergedConfig();

        $out = [];
        foreach ($merged as $name => $pipeline) {
            $out[$name] = $this->stepsOf($pipeline);
        }

        return $out;
    }

    /**
     * Return the normalized form for a single pipeline:
     * `['description' => string, 'steps' => array]`, or null if unknown.
     */
    public function getPipeline(string $name): ?array
    {
        $merged = $this->mergedConfig();

        if (!array_key_exists($name, $merged)) {
            return null;
        }

        return [
            'description' => $this->descriptionOf($merged[$name]),
            'steps' => $this->stepsOf($merged[$name]),
        ];
    }

    /**
     * Return the registered pipeline names.
     *
     * @return string[]
     */
    public function getPipelineNames(): array
    {
        return array_keys($this->mergedConfig());
    }

    /**
     * Return `[name => description]` for every registered pipeline.
     *
     * @return array<string, string>
     */
    public function getDescriptions(): array
    {
        $out = [];
        foreach ($this->mergedConfig() as $name => $pipeline) {
            $out[$name] = $this->descriptionOf($pipeline);
        }

        return $out;
    }

    protected function mergedConfig(): array
    {
        $configPipelines = config('generator.pipelines', []);

        return array_merge($configPipelines, $this->pipelines);
    }

    /**
     * Detect hybrid shape: an associative array with a 'steps' array under the
     * 'steps' key. Anything else is treated as a steps array directly (legacy).
     */
    protected function isHybridShape($pipeline): bool
    {
        return is_array($pipeline)
            && array_key_exists('steps', $pipeline)
            && is_array($pipeline['steps']);
    }

    protected function stepsOf($pipeline): array
    {
        if ($this->isHybridShape($pipeline)) {
            return $pipeline['steps'];
        }

        return is_array($pipeline) ? $pipeline : [];
    }

    protected function descriptionOf($pipeline): string
    {
        if ($this->isHybridShape($pipeline) && isset($pipeline['description'])) {
            return (string) $pipeline['description'];
        }

        return '';
    }
}
