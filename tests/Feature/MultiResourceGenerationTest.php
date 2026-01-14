<?php

namespace Firevel\Generator\Tests\Feature;

use Firevel\Generator\PipelineContext;
use Firevel\Generator\Resource;
use Firevel\Generator\ScopedPipelineRunner;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;

class MultiResourceGenerationTest extends TestCase
{
    use WithWorkbench;

    /** @test */
    public function test_generic_app_pipeline_generates_multiple_resources()
    {
        $resourceData = [
            'resources' => [
                [
                    'name' => 'post',
                    'model' => [
                        'fillable' => ['title', 'content'],
                    ],
                ],
                [
                    'name' => 'comment',
                    'model' => [
                        'fillable' => ['body'],
                    ],
                ],
            ],
        ];

        $resource = new Resource($resourceData);

        $pipelines = config('generator.pipelines');

        $appPipeline = $pipelines['generic-app'];
        $context = new PipelineContext(true);

        $runner = new ScopedPipelineRunner($resource, $appPipeline, $pipelines, $context);

        $logger = new class {
            public function info($message) {}
            public function error($message) {}
            public function warn($message) {}
        };

        $runner->setLogger($logger);
        $runner->execute();

        // Verify routes were collected
        $this->assertTrue($context->has('routes'));
        $routes = $context->get('routes');
        $this->assertCount(2, $routes);
        $this->assertEquals('posts', $routes[0]['name']);
        $this->assertEquals('comments', $routes[1]['name']);
    }

    /** @test */
    public function test_generic_app_pipeline_does_not_require_service_config()
    {
        // generic-app should work without service configuration (unlike appengine-app)
        $resourceData = [
            'resources' => [
                [
                    'name' => 'article',
                    'model' => [
                        'fillable' => ['title'],
                    ],
                ],
            ],
        ];

        $resource = new Resource($resourceData);

        $pipelines = config('generator.pipelines');

        $appPipeline = $pipelines['generic-app'];
        $context = new PipelineContext(true);

        $runner = new ScopedPipelineRunner($resource, $appPipeline, $pipelines, $context);

        $logger = new class {
            public function info($message) {}
            public function error($message) {}
            public function warn($message) {}
        };

        $runner->setLogger($logger);
        $runner->execute();

        // Verify the pipeline completed successfully
        $this->assertTrue($context->has('routes'));
        $routes = $context->get('routes');
        $this->assertCount(1, $routes);
        $this->assertEquals('articles', $routes[0]['name']);
    }

    /** @test */
    public function test_appengine_app_pipeline_generates_multiple_resources()
    {
        $resourceData = [
            'service' => [
                'name' => 'blog-api',
                'runtime' => 'php83',
            ],
            'resources' => [
                [
                    'name' => 'post',
                    'model' => [
                        'fillable' => ['title', 'content'],
                    ],
                ],
                [
                    'name' => 'comment',
                    'model' => [
                        'fillable' => ['body'],
                    ],
                ],
            ],
        ];

        $resource = new Resource($resourceData);

        $pipelines = config('generator.pipelines');

        $appPipeline = $pipelines['appengine-app'];
        $context = new PipelineContext(true);

        $runner = new ScopedPipelineRunner($resource, $appPipeline, $pipelines, $context);

        // Mock logger to avoid console output in tests
        $logger = new class {
            public function info($message) {}
            public function error($message) {}
            public function warn($message) {}
        };

        $runner->setLogger($logger);
        $runner->execute();

        // Verify routes were collected
        $this->assertTrue($context->has('routes'));
        $routes = $context->get('routes');
        $this->assertCount(2, $routes);
        $this->assertEquals('posts', $routes[0]['name']);
        $this->assertEquals('comments', $routes[1]['name']);
    }

    /** @test */
    public function test_context_shared_across_resource_iterations()
    {
        $resourceData = [
            'resources' => [
                ['name' => 'user'],
                ['name' => 'role'],
                ['name' => 'permission'],
            ],
        ];

        $resource = new Resource($resourceData);

        $pipelines = config('generator.pipelines');

        $scopedSteps = [
            [
                'scope' => 'resources.*',
                'pipeline' => 'api-resource',
            ],
            [
                'scope' => 'resources',
                'pipeline' => 'routes',
            ],
        ];

        $context = new PipelineContext(true);
        $runner = new ScopedPipelineRunner($resource, $scopedSteps, $pipelines, $context);

        $logger = new class {
            public function info($message) {}
            public function error($message) {}
            public function warn($message) {}
        };

        $runner->setLogger($logger);
        $runner->execute();

        // Verify all three resources collected routes
        $routes = $context->get('routes');
        $this->assertCount(3, $routes);
        $this->assertEquals('users', $routes[0]['name']);
        $this->assertEquals('roles', $routes[1]['name']);
        $this->assertEquals('permissions', $routes[2]['name']);
    }

    /** @test */
    public function test_scoped_pipeline_resolves_nested_data()
    {
        $resourceData = [
            'service' => [
                'name' => 'test-service',
                'config' => [
                    'database' => 'mysql',
                ],
            ],
        ];

        $resource = new Resource($resourceData);

        // Test that we can access nested data via scope
        $this->assertTrue($resource->has('service'));
        $this->assertTrue($resource->has('service.name'));
        $this->assertEquals('test-service', $resource->get('service.name'));
        $this->assertEquals('mysql', $resource->get('service.config.database'));
    }

    /** @test */
    public function test_empty_resources_array_doesnt_fail()
    {
        $resourceData = [
            'service' => ['name' => 'test'],
            'resources' => [],
        ];

        $resource = new Resource($resourceData);

        $pipelines = config('generator.pipelines');

        $scopedSteps = [
            [
                'scope' => 'resources.*',
                'pipeline' => 'api-resource',
            ],
        ];

        $context = new PipelineContext(true);
        $runner = new ScopedPipelineRunner($resource, $scopedSteps, $pipelines, $context);

        $logger = new class {
            public function info($message) {}
            public function error($message) {}
            public function warn($message) {}
        };

        $runner->setLogger($logger);
        $runner->execute();

        // Should complete without errors, no routes collected
        $this->assertFalse($context->has('routes'));
    }

    /** @test */
    public function test_generators_can_access_full_input_via_context()
    {
        $resourceData = [
            'service' => [
                'name' => 'blog-api',
                'runtime' => 'php83',
            ],
            'resources' => [
                [
                    'name' => 'post',
                    'model' => [
                        'fillable' => ['title'],
                    ],
                ],
            ],
        ];

        $resource = new Resource($resourceData);

        $pipelines = config('generator.pipelines');

        $scopedSteps = [
            [
                'scope' => 'resources.*',
                'pipeline' => 'api-resource',
            ],
        ];

        $context = new PipelineContext(true);
        $runner = new ScopedPipelineRunner($resource, $scopedSteps, $pipelines, $context);

        $logger = new class {
            public function info($message) {}
            public function error($message) {}
            public function warn($message) {}
        };

        $runner->setLogger($logger);
        $runner->execute();

        // Verify the full input was stored in context
        $this->assertTrue($context->has('input'));
        $fullInput = $context->get('input');
        $this->assertInstanceOf(Resource::class, $fullInput);
        $this->assertEquals('blog-api', $fullInput->get('service.name'));
        $this->assertEquals('php83', $fullInput->get('service.runtime'));
    }

    protected function getPackageProviders($app)
    {
        return [
            \Firevel\Generator\ServiceProvider::class,
        ];
    }
}
