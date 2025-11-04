<?php

namespace Firevel\Generator\Tests\Generator\ApiResource;

use Firevel\Generator\Generators\ApiResource\RouteGenerator;
use Firevel\Generator\PipelineContext;
use Firevel\Generator\Resource;
use Orchestra\Testbench\TestCase;
use Mockery;

class RouteGeneratorTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function test_standalone_mode_logs_route_instructions()
    {
        $resource = new Resource([
            'name' => 'post',
        ]);

        $context = new PipelineContext(false); // Standalone mode
        $generator = new RouteGenerator($resource, $context);

        $logger = Mockery::mock('stdClass');
        $logger->shouldReceive('info')
            ->with("# Generating route")
            ->once()
            ->andReturn(null);
        $logger->shouldReceive('info')
            ->with("- [Required] Register the API route: Route::apiResource('posts', \\App\\Http\\Controllers\\Api\\PostsController::class);")
            ->once()
            ->andReturn(null);

        $generator->setLogger($logger);
        $generator->handle();

        // Verify context doesn't have routes
        $this->assertFalse($context->has('routes'));
    }

    /** @test */
    public function test_meta_pipeline_mode_collects_route_data()
    {
        $resource = new Resource([
            'name' => 'post',
        ]);

        $context = new PipelineContext(true); // Meta-pipeline mode
        $generator = new RouteGenerator($resource, $context);

        $logger = Mockery::mock('stdClass');
        $logger->shouldReceive('info')
            ->with("# Route collected: posts")
            ->once()
            ->andReturn(null);

        $generator->setLogger($logger);
        $generator->handle();

        // Verify context has collected route
        $this->assertTrue($context->has('routes'));
        $routes = $context->get('routes');
        $this->assertCount(1, $routes);
        $this->assertEquals('posts', $routes[0]['name']);
        $this->assertEquals('\\App\\Http\\Controllers\\Api\\PostsController::class', $routes[0]['controller']);
        $this->assertEquals('Posts', $routes[0]['resourceName']);
    }

    /** @test */
    public function test_multiple_resources_collect_multiple_routes()
    {
        $context = new PipelineContext(true);

        // First resource
        $resource1 = new Resource(['name' => 'post']);
        $generator1 = new RouteGenerator($resource1, $context);

        $logger = Mockery::mock('stdClass');
        $logger->shouldReceive('info')->andReturn(null);

        $generator1->setLogger($logger);
        $generator1->handle();

        // Second resource
        $resource2 = new Resource(['name' => 'comment']);
        $generator2 = new RouteGenerator($resource2, $context);
        $generator2->setLogger($logger);
        $generator2->handle();

        // Verify both routes collected
        $routes = $context->get('routes');
        $this->assertCount(2, $routes);
        $this->assertEquals('posts', $routes[0]['name']);
        $this->assertEquals('comments', $routes[1]['name']);
    }

    /** @test */
    public function test_route_name_converts_underscores_to_dashes()
    {
        $resource = new Resource([
            'name' => 'blog_post',
        ]);

        $context = new PipelineContext(true);
        $generator = new RouteGenerator($resource, $context);

        $logger = Mockery::mock('stdClass');
        $logger->shouldReceive('info')->andReturn(null);

        $generator->setLogger($logger);
        $generator->handle();

        $routes = $context->get('routes');
        $this->assertEquals('blog-posts', $routes[0]['name']);
    }

    /** @test */
    public function test_route_code_contains_proper_format()
    {
        $resource = new Resource([
            'name' => 'category',
        ]);

        $context = new PipelineContext(true);
        $generator = new RouteGenerator($resource, $context);

        $logger = Mockery::mock('stdClass');
        $logger->shouldReceive('info')->andReturn(null);

        $generator->setLogger($logger);
        $generator->handle();

        $routes = $context->get('routes');
        $expectedCode = "Route::apiResource('categories', \\App\\Http\\Controllers\\Api\\CategoriesController::class);";
        $this->assertEquals($expectedCode, $routes[0]['code']);
    }
}
