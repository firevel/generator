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
}