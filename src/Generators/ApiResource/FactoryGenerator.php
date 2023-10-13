<?php

namespace Firevel\Generator\Generators\ApiResource;

use Firevel\ApiResourceGenerator\Resource;
use Firevel\Generator\Generators\BaseGenerator;

class FactoryGenerator extends BaseGenerator
{
    public function handle()
    {
        $resource = $this->resource();
        $name = $resource->name;

        $this->artisan(
            'make:factory',
            [
                'name' => $name,
            ]
        );
        $this->logger()->info("# Factory created: {$name}Factory");
        $this->logger()->info('- [Optional] Set factory fields.');
        $this->logger()->info('  - Available formatters https://fakerphp.github.io/');
    }
}