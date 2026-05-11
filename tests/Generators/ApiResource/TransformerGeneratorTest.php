<?php

namespace Firevel\Generator\Tests\Generator\ApiResource;

use Firevel\Generator\Generators\App\ComposerRequireGenerator;
use Firevel\Generator\Generators\ApiResource\TransformerGenerator;
use Firevel\Generator\PipelineContext;
use Firevel\Generator\Resource;
use Orchestra\Testbench\Concerns\WithWorkbench;

class TransformerGeneratorTest extends \Orchestra\Testbench\TestCase
{
    use WithWorkbench;

    protected ?string $composerBackup = null;
    protected string $composerPath;
    protected string $transformerDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->composerPath = base_path('composer.json');
        if (file_exists($this->composerPath)) {
            $this->composerBackup = file_get_contents($this->composerPath);
        }

        $this->transformerDir = app_path('Transformers');
    }

    protected function tearDown(): void
    {
        if ($this->composerBackup !== null) {
            file_put_contents($this->composerPath, $this->composerBackup);
        }

        // Clean up any generated transformer files.
        if (is_dir($this->transformerDir)) {
            foreach (glob($this->transformerDir . '/*.php') as $f) {
                unlink($f);
            }
        }

        parent::tearDown();
    }

    protected function makeLogger()
    {
        return new class {
            public function info($m) {}
            public function error($m) {}
            public function warn($m) {}
            public function confirm($m, $d = true) { return true; }
        };
    }

    public function test_transformer_generator_pushes_spatie_laravel_fractal_require()
    {
        $context = new PipelineContext(true);

        $generator = new TransformerGenerator(new Resource(['name' => 'Post']), $context);
        $generator->setLogger($this->makeLogger());
        $generator->handle();

        $this->assertSame(
            ['spatie/laravel-fractal' => '^6.0'],
            $context->get('composer_requires')
        );
    }

    public function test_push_flows_through_composer_require_generator_into_composer_json()
    {
        $context = new PipelineContext(true);
        $context->set('input', new Resource([]));

        $transformer = new TransformerGenerator(new Resource(['name' => 'Post']), $context);
        $transformer->setLogger($this->makeLogger());
        $transformer->handle();

        $composer = new ComposerRequireGenerator(new Resource([]), $context);
        $composer->setLogger($this->makeLogger());
        $composer->handle();

        $composerJson = json_decode(file_get_contents($this->composerPath), true);
        $this->assertSame('^6.0', $composerJson['require']['spatie/laravel-fractal']);
    }

    public function test_app_level_require_overrides_transformer_push()
    {
        $context = new PipelineContext(true);
        $context->set('input', new Resource([
            'require' => ['spatie/laravel-fractal' => '^5.0'],
        ]));

        $transformer = new TransformerGenerator(new Resource(['name' => 'Post']), $context);
        $transformer->setLogger($this->makeLogger());
        $transformer->handle();

        $composer = new ComposerRequireGenerator(new Resource([]), $context);
        $composer->setLogger($this->makeLogger());
        $composer->handle();

        $composerJson = json_decode(file_get_contents($this->composerPath), true);
        $this->assertSame('^5.0', $composerJson['require']['spatie/laravel-fractal']);
    }

    public function test_resource_wildcard_defers_to_transformer_push()
    {
        $context = new PipelineContext(true);
        $context->set('input', new Resource([
            'resources' => [
                ['name' => 'Post', 'require' => ['spatie/laravel-fractal' => '*']],
            ],
        ]));

        $transformer = new TransformerGenerator(new Resource(['name' => 'Post']), $context);
        $transformer->setLogger($this->makeLogger());
        $transformer->handle();

        $composer = new ComposerRequireGenerator(new Resource([]), $context);
        $composer->setLogger($this->makeLogger());
        $composer->handle();

        $composerJson = json_decode(file_get_contents($this->composerPath), true);
        $this->assertSame('^6.0', $composerJson['require']['spatie/laravel-fractal']);
    }
}
