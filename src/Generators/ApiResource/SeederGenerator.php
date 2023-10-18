<?php

namespace Firevel\Generator\Generators\ApiResource;

use Firevel\ApiResourceGenerator\Resource;
use Firevel\Generator\Generators\BaseGenerator;

class SeederGenerator extends BaseGenerator
{
    public function handle()
    {
        $resource = $this->resource();
        $name = $resource->name . 'Seeder';

        $path = database_path('seeders') . '/' . "{$name}.php";

        $source = $this->render(
            'api-resource/seeder',
            [
                'resource' => $resource,
            ]
        );
        $this->createFile($path, $source);

        $this->logger()->info("# Seeder created: {$name}");
        $this->logger()->info("- [Optional] Add factory to seeder");
        $this->logger()->info("  - Example: \\App\Models\\" . $resource->name() . "::factory()->count(50)->create();");
        $this->logger()->info("- [Optional] Add seeder to DatabaseSeeder");
        $this->logger()->info("  - Add \$this->call({$name}::class); to database/seeders/DatabaseSeeder.php");

    }
}