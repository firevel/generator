<?php

namespace Firevel\Generator;

use Firevel\Generator\ResourceCollection;
use Firevel\Generator\ResourceItem;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Class Resource
 * @package Firevel\Generator
 */
class Resource implements Arrayable
{
    /**
     * Array with resource attributes.
     *
     * @var array
     */
    public $attributes = [];

    /**
     * Constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    /**
     * Check if resource got no attributes.
     *
     * @return bool
     */
    public function empty()
    {
        return empty($this->attributes);
    }

    /**
     * Check if resource got some attributes.
     *
     * @return bool
     */
    public function notEmpty()
    {
        return ! empty($this->attributes);
    }

    /**
     * Get all attributes as array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->attributes;
    }

    /**
     * Determine if an attribute exists.
     *
     * @param  string  $key
     * @return bool
     */
    public function has($key)
    {
        if (strpos($key, '.')) {
            return Arr::has($this->attributes, $key);
        }
        return ! empty($this->attributes[$key]);
    }

    /**
     * Get resource value.
     * @param  string $key
     * @return mixed
     */
    public function get($key)
    {
        if (strpos($key, '.')) {
            return Arr::get($this->attributes, $key);
        }
        return $this->attributes[$key];
    }

    /**
     * Dynamically retrieve attribute.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->attributes[$key];
    }

    /**
     * Dynamically set attribute.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Determine if an attribute exists.
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return $this->has($key);
    }

    /**
     * Handle dynamic method calls.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (empty($this->attributes[$method])) {
            return null;
        }

        if (is_array($this->attributes[$method])) {
            return new ResourceCollection($this->attributes[$method]);
        }

        if (is_string($this->attributes[$method])) {
            return new ResourceItem($this->attributes[$method]);
        }

        throw new \Exception('Unsupported attribute type');
    }
}
