<?php

return [
    'pipelines' => [
        'app.yaml' => [
            'description' => 'Generate a Google App Engine app.yaml service descriptor.',
            'steps' => [
                'yaml' => \Firevel\Generator\Generators\App\YamlGenerator::class,
            ],
        ],
        'api-resource' => [
            'description' => 'Generate one full API resource: migration, model, transformer, controller, requests, factory, seeder, policy and route.',
            'steps' => [
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
        ],
        'api-resources' => [
            'description' => 'Run the api-resource pipeline for every entry under `resources.*`.',
            'steps' => [
                [
                    'scope' => 'resources.*',
                    'pipeline' => 'api-resource',
                ],
            ],
        ],
        'routes' => [
            'description' => 'Consolidate routes collected by resource pipelines into routes/api.php.',
            'steps' => [
                'consolidate' => \Firevel\Generator\Generators\ApiResource\RoutesConsolidatorGenerator::class,
            ],
        ],
        'appengine-app' => [
            'description' => 'Generate a complete App Engine application: app.yaml service, all resources, routes, morph map, composer requires and .env.',
            'steps' => [
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
                'morph-map' => \Firevel\Generator\Generators\App\MorphMapGenerator::class,
                'composer-require' => \Firevel\Generator\Generators\App\ComposerRequireGenerator::class,
                'env' => \Firevel\Generator\Generators\App\EnvGenerator::class,
            ],
        ],
        'generic-app' => [
            'description' => 'Generate a generic Laravel application: all resources, routes, morph map, composer requires and .env (no App Engine service).',
            'steps' => [
                [
                    'scope' => 'resources.*',
                    'pipeline' => 'api-resource',
                ],
                [
                    'scope' => 'resources',
                    'pipeline' => 'routes',
                ],
                'morph-map' => \Firevel\Generator\Generators\App\MorphMapGenerator::class,
                'composer-require' => \Firevel\Generator\Generators\App\ComposerRequireGenerator::class,
                'env' => \Firevel\Generator\Generators\App\EnvGenerator::class,
            ],
        ],
   ],
];
