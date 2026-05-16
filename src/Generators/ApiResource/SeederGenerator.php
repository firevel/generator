<?php

namespace Firevel\Generator\Generators\ApiResource;

use Firevel\ApiResourceGenerator\Resource;
use Firevel\Generator\Generators\BaseGenerator;

class SeederGenerator extends BaseGenerator
{
    public static function description(): string
    {
        return 'Generates a database seeder stub for the resource.';
    }

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

        $this->addManualStep("register seeder: add \$this->call({$name}::class) to database/seeders/DatabaseSeeder.php");
    }
}