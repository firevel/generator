<?php

namespace Firevel\Generator;

use Illuminate\Support\Str;

class ResourceItem
{
    /**
     * Raw value.
     *
     * @var string
     */
    public $value;

    public function __construct(string $value) {
        $this->value = $value;
    }

    public function singular(): self {
        $this->value = Str::singular($this->value);
        return $this;
    }

    public function plural(): self {
        $this->value = Str::plural($this->value);
        return $this;
    }

    public function snake(): self {
        $this->value = Str::snake($this->value);
        return $this;
    }

    public function slug(): self {
        $this->value = Str::slug($this->value);
        return $this;
    }

    public function studly(): self {
        $this->value = Str::studly($this->value);
        return $this;
    }

    public function camel(): self {
        $this->value = Str::camel($this->value);
        return $this;
    }

    public function kebab(): self {
        $this->value = Str::kebab($this->value);
        return $this;
    }

    public function lcfirst(): self {
        $this->value = lcfirst($this->value);
        return $this;
    }

    public function __toString() {
        return $this->value;
    }
}
