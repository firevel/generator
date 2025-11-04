<?php

namespace Firevel\Generator\Tests;

use Firevel\Generator\PipelineContext;
use Firevel\Generator\Resource;
use Firevel\Generator\ScopedPipelineRunner;
use Orchestra\Testbench\TestCase;
use Mockery;

class ScopedPipelineRunnerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function test_executes_single_scope_pipeline()
    {
        $resource = new Resource([
            'service' => [
                'name' => 'test-service',
                'runtime' => 'php83',
            ],
        ]);

        $scopedSteps = [
            [
                'scope' => 'service',
                'pipeline' => 'test-pipeline',
            ],
        ];

        $pipelines = [
            'test-pipeline' => [
                'test-generator' => MockGenerator::class,
            ],
        ];

        $context = new PipelineContext(true);
        $logger = Mockery::mock('stdClass');
        $logger->shouldReceive('info')->andReturn(null);

        $runner = new ScopedPipelineRunner($resource, $scopedSteps, $pipelines, $context);
        $runner->setLogger($logger);
        $runner->execute();

        // If no exception is thrown, the test passes
        $this->assertTrue(true);
    }

    /** @test */
    public function test_executes_iterated_scope_pipeline()
    {
        $resource = new Resource([
            'resources' => [
                ['name' => 'post'],
                ['name' => 'comment'],
            ],
        ]);

        $scopedSteps = [
            [
                'scope' => 'resources.*',
                'pipeline' => 'test-pipeline',
            ],
        ];

        $pipelines = [
            'test-pipeline' => [
                'test-generator' => MockGenerator::class,
            ],
        ];

        $context = new PipelineContext(true);
        $logger = Mockery::mock('stdClass');
        $logger->shouldReceive('info')->atLeast()->once()->andReturn(null);

        $runner = new ScopedPipelineRunner($resource, $scopedSteps, $pipelines, $context);
        $runner->setLogger($logger);
        $runner->execute();

        $this->assertTrue(true);
    }

    /** @test */
    public function test_logs_error_for_missing_pipeline()
    {
        $resource = new Resource([
            'service' => ['name' => 'test'],
        ]);

        $scopedSteps = [
            [
                'scope' => 'service',
                'pipeline' => 'non-existent-pipeline',
            ],
        ];

        $pipelines = [];

        $context = new PipelineContext(true);
        $logger = Mockery::mock('stdClass');
        $logger->shouldReceive('error')
            ->with("Pipeline 'non-existent-pipeline' not found")
            ->once()
            ->andReturn(null);

        $runner = new ScopedPipelineRunner($resource, $scopedSteps, $pipelines, $context);
        $runner->setLogger($logger);
        $runner->execute();

        $this->assertTrue(true);
    }

    /** @test */
    public function test_logs_warning_for_non_iterable_scope()
    {
        $resource = new Resource([
            'service' => 'just-a-string',
        ]);

        $scopedSteps = [
            [
                'scope' => 'service.*',
                'pipeline' => 'test-pipeline',
            ],
        ];

        $pipelines = [
            'test-pipeline' => [],
        ];

        $context = new PipelineContext(true);
        $logger = Mockery::mock('stdClass');
        $logger->shouldReceive('warn')
            ->with("Scope 'service' is not iterable")
            ->once()
            ->andReturn(null);

        $runner = new ScopedPipelineRunner($resource, $scopedSteps, $pipelines, $context);
        $runner->setLogger($logger);
        $runner->execute();

        $this->assertTrue(true);
    }

    /** @test */
    public function test_executes_multiple_scoped_steps_in_sequence()
    {
        $resource = new Resource([
            'service' => ['name' => 'test-service'],
            'resources' => [
                ['name' => 'post'],
            ],
        ]);

        $scopedSteps = [
            [
                'scope' => 'service',
                'pipeline' => 'service-pipeline',
            ],
            [
                'scope' => 'resources.*',
                'pipeline' => 'resource-pipeline',
            ],
        ];

        $pipelines = [
            'service-pipeline' => [
                'test-generator' => MockGenerator::class,
            ],
            'resource-pipeline' => [
                'test-generator' => MockGenerator::class,
            ],
        ];

        $context = new PipelineContext(true);
        $logger = Mockery::mock('stdClass');
        $logger->shouldReceive('info')->andReturn(null);

        $runner = new ScopedPipelineRunner($resource, $scopedSteps, $pipelines, $context);
        $runner->setLogger($logger);
        $runner->execute();

        $this->assertTrue(true);
    }
}

// Mock Generator for testing
class MockGenerator
{
    protected $resource;
    protected $context;
    protected $logger;

    public function __construct($resource, $context = null)
    {
        $this->resource = $resource;
        $this->context = $context;
    }

    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    public function handle()
    {
        // Do nothing - this is a mock
    }
}
