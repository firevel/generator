<?php

namespace Firevel\Generator;

class PipelineContext
{
    protected array $data = [];
    protected bool $isMetaPipeline = false;

    public function __construct(bool $isMetaPipeline = false)
    {
        $this->isMetaPipeline = $isMetaPipeline;
    }

    /**
     * Check if this context is part of a meta-pipeline execution
     *
     * @return bool
     */
    public function isMetaPipeline(): bool
    {
        return $this->isMetaPipeline;
    }

    /**
     * Set a value in the context
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set(string $key, $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * Get a value from the context
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Push a value onto an array in the context
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function push(string $key, $value): void
    {
        if (!isset($this->data[$key])) {
            $this->data[$key] = [];
        }
        $this->data[$key][] = $value;
    }

    /**
     * Check if a key exists in the context
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    /**
     * Get all context data
     *
     * @return array
     */
    public function all(): array
    {
        return $this->data;
    }
}
