<?php

return [
    'pipelines' => [
        'app.yaml' => [
            'yaml' => \Firevel\Generator\Generators\App\YamlGenerator::class,
        ],
        'api-resource' => [
            'get-parameters' => \Firevel\Generator\Generators\ApiResource\GetParameters::class,
            'migrations' => \Firevel\Generator\Generators\ApiResource\MigrationsGenerator::class,
            'model' => \Firevel\Generator\Generators\ApiResource\ModelGenerator::class,
            'transformer' => \Firevel\Generator\Generators\ApiResource\TransformerGenerator::class,
            'controller' => \Firevel\Generator\Generators\ApiResource\ControllerGenerator::class,
            'requests' => \Firevel\Generator\Generators\ApiResource\RequestsGenerator::class,
            'factory' => \Firevel\Generator\Generators\ApiResource\FactoryGenerator::class,
            'seeder' => \Firevel\Generator\Generators\ApiResource\SeederGenerator::class,
            'policy' => \Firevel\Generator\Generators\ApiResource\PolicyGenerator::class,
            'route' => \Firevel\Generator\Generators\ApiResource\RouteGenerator::class,
        ],
        'routes' => [
            'consolidate' => \Firevel\Generator\Generators\ApiResource\RoutesConsolidatorGenerator::class,
        ],
        'app' => [
            [
                'scope' => 'service',
                'pipeline' => 'app.yaml',
            ],
            [
                'scope' => 'resources.*',
                'pipeline' => 'api-resource',
            ],
            [
                'scope' => 'resources',
                'pipeline' => 'routes',
            ],
        ],
   ],
];
