<?php

namespace Tests\Unit;

use Firevel\Generator\FirevelGeneratorManager;
use Illuminate\Support\Facades\Config;

class FirevelGeneratorManagerTest extends \Orchestra\Testbench\TestCase
{
    protected $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = new FirevelGeneratorManager();

        $config = [
            'api-resource' => [
                'model' => \Firevel\Generator\Generators\ApiResource\ModelGenerator::class,
            ],
            'app.yaml' => [
                'yaml' => \Firevel\Generator\Generators\App\YamlGenerator::class,
            ],
        ];
        Config::set('generator.pipelines', $config);
    }

    public function testExtendRegistersPipeline()
    {
        $this->manager->extend('custom-pipeline', [
            'task-1' => \App\Generators\CustomTask::class,
            'task-2' => \App\Generators\AnotherCustomTask::class,
        ]);

        $pipelines = $this->manager->getPipelines();

        $this->assertArrayHasKey('custom-pipeline', $pipelines);
        $this->assertEquals([
            'task-1' => \App\Generators\CustomTask::class,
            'task-2' => \App\Generators\AnotherCustomTask::class,
        ], $pipelines['custom-pipeline']);
    }

    public function testConfigOverridesExtendedPipeline()
    {
        $this->manager->extend('api-resource', [
            'controller' => \App\Generators\CustomControllerGenerator::class,
        ]);

        $pipelines = $this->manager->getPipelines();

        $this->assertEquals(\App\Generators\CustomControllerGenerator::class, $pipelines['api-resource']['controller']);
    }

    public function testGetPipelinesReturnsMergedConfiguration()
    {
        $this->manager->extend('new-pipeline', [
            'task' => \App\Generators\NewPipelineTask::class,
        ]);

        $pipelines = $this->manager->getPipelines();

        $expected = [
            'api-resource' => [
                'model' => \Firevel\Generator\Generators\ApiResource\ModelGenerator::class,
            ],
            'app.yaml' => [
                'yaml' => \Firevel\Generator\Generators\App\YamlGenerator::class,
            ],
            'new-pipeline' => [
                'task' => \App\Generators\NewPipelineTask::class,
            ],
        ];

        $this->assertEquals($expected, $pipelines);
    }

    public function testGetPipelinesFlattensHybridShapeToSteps()
    {
        Config::set('generator.pipelines', [
            'hybrid' => [
                'description' => 'A pipeline with metadata.',
                'steps' => [
                    'task' => \App\Generators\NewPipelineTask::class,
                ],
            ],
        ]);

        $pipelines = $this->manager->getPipelines();

        $this->assertEquals(
            ['task' => \App\Generators\NewPipelineTask::class],
            $pipelines['hybrid']
        );
    }

    public function testGetPipelineReturnsNormalizedHybridShape()
    {
        Config::set('generator.pipelines', [
            'hybrid' => [
                'description' => 'A pipeline with metadata.',
                'steps' => [
                    'task' => \App\Generators\NewPipelineTask::class,
                ],
            ],
        ]);

        $pipeline = $this->manager->getPipeline('hybrid');

        $this->assertSame('A pipeline with metadata.', $pipeline['description']);
        $this->assertSame(
            ['task' => \App\Generators\NewPipelineTask::class],
            $pipeline['steps']
        );
    }

    public function testGetPipelineReturnsEmptyDescriptionForLegacyShape()
    {
        $pipeline = $this->manager->getPipeline('api-resource');

        $this->assertSame('', $pipeline['description']);
        $this->assertArrayHasKey('model', $pipeline['steps']);
    }

    public function testGetPipelineReturnsNullForUnknown()
    {
        $this->assertNull($this->manager->getPipeline('does-not-exist'));
    }

    public function testGetDescriptionsListsAllPipelines()
    {
        Config::set('generator.pipelines', [
            'legacy' => [
                'task' => \App\Generators\NewPipelineTask::class,
            ],
            'hybrid' => [
                'description' => 'Hybrid one.',
                'steps' => [
                    'task' => \App\Generators\NewPipelineTask::class,
                ],
            ],
        ]);

        $descriptions = $this->manager->getDescriptions();

        $this->assertSame('', $descriptions['legacy']);
        $this->assertSame('Hybrid one.', $descriptions['hybrid']);
    }

    public function testExtendWithHybridShapeIsNormalized()
    {
        $this->manager->extend('runtime-hybrid', [
            'description' => 'Registered at runtime.',
            'steps' => [
                'task' => \App\Generators\NewPipelineTask::class,
            ],
        ]);

        $pipeline = $this->manager->getPipeline('runtime-hybrid');

        $this->assertSame('Registered at runtime.', $pipeline['description']);
        $this->assertSame(
            ['task' => \App\Generators\NewPipelineTask::class],
            $pipeline['steps']
        );

        // getPipelines() flattens for backward compatibility.
        $pipelines = $this->manager->getPipelines();
        $this->assertSame(
            ['task' => \App\Generators\NewPipelineTask::class],
            $pipelines['runtime-hybrid']
        );
    }
}