<?php

namespace Firevel\Generator\Generators\ApiResource;

use Firevel\ApiResourceGenerator\Resource;
use Firevel\Generator\Generators\BaseGenerator;

class MigrationGenerator extends BaseGenerator
{
    public function handle()
    {
        $resource = $this->resource();

        $name = "create_{$resource->name()->plural()->snake()}_table";

        $this->artisan(
            'make:migration',
            [
                'name' => $name,
                '--create' => $resource->name()->plural()->snake(),
            ]
        );
        $this->logger()->info("# Migration created: {$name}");
        $this->logger()->info('- [Required] Set migration');
        $this->logger()->info('  - Available column types https://laravel.com/docs/migrations#available-column-types)');
    }
}