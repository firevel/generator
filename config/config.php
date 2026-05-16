<?php

return [
    'pipelines' => [
        'app.yaml' => [
            'description' => 'Generate a Google App Engine app.yaml service descriptor.',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'resources' => ['not' => new \stdClass()],
                ],
            ],
            'input_error_messages' => [
                '/resources' => "Pipeline 'app.yaml' generates a single service descriptor and does not accept a `resources` array. "
                    . "Use 'appengine-app' to generate service + resources together.",
            ],
            'steps' => [
                'yaml' => \Firevel\Generator\Generators\App\YamlGenerator::class,
            ],
        ],
        'api-resource' => [
            'description' => 'Generate one full API resource: migration, model, transformer, controller, requests, factory, seeder, policy and route.',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'resources' => ['not' => new \stdClass()],
                ],
            ],
            'input_error_messages' => [
                '/resources' => "Pipeline 'api-resource' generates a single resource and does not accept a `resources` array. "
                    . "If you have multi-resource input, use 'api-resources' (resources only) or 'generic-app'/'appengine-app' (full app).",
            ],
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
            'input_schema' => [
                'type' => 'object',
                'required' => ['resources'],
                'properties' => [
                    'resources' => [
                        'type' => 'array',
                        'minItems' => 1,
                    ],
                ],
            ],
            'input_error_messages' => [
                '/resources' => "Pipeline 'api-resources' iterates `resources.*`. Provide a non-empty top-level `resources` array, "
                    . "e.g. {\"resources\":[{\"name\":\"Article\",...},{\"name\":\"Comment\",...}]}.",
            ],
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
            'input_schema' => [
                'type' => 'object',
                'required' => ['service', 'resources'],
                'properties' => [
                    'resources' => [
                        'type' => 'array',
                        'minItems' => 1,
                    ],
                    'service' => [
                        'type' => 'object',
                    ],
                ],
            ],
            'input_error_messages' => [
                '/service' => "Pipeline 'appengine-app' needs an App Engine service descriptor under top-level `service` "
                    . "(name, runtime, env_variables, etc).",
                '/resources' => "Pipeline 'appengine-app' iterates `resources.*`. Provide a non-empty top-level `resources` array.",
            ],
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
            'input_schema' => [
                'type' => 'object',
                'required' => ['resources'],
                'properties' => [
                    'resources' => [
                        'type' => 'array',
                        'minItems' => 1,
                    ],
                    'service' => ['not' => new \stdClass()],
                ],
            ],
            'input_error_messages' => [
                '/resources' => "Pipeline 'generic-app' iterates `resources.*`. Provide a non-empty top-level `resources` array.",
                '/service' => "Pipeline 'generic-app' does not consume a `service` block — use 'appengine-app' if you want an App Engine service descriptor generated alongside the resources.",
            ],
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
