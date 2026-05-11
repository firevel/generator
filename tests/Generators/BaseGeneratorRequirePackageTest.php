<?php

namespace Firevel\Generator\Tests\Generators;

use Firevel\Generator\Generators\BaseGenerator;
use Firevel\Generator\PipelineContext;
use Firevel\Generator\Resource;
use Orchestra\Testbench\Concerns\WithWorkbench;

class BaseGeneratorRequirePackageTest extends \Orchestra\Testbench\TestCase
{
    use WithWorkbench;

    protected function makeGenerator(PipelineContext $context, $logger = null): BaseGenerator
    {
        $generator = new class(new Resource([]), $context) extends BaseGenerator {
            public function handle()
            {
            }

            public function pushPackage(string $name, string $version): void
            {
                $this->requirePackage($name, $version);
            }
        };

        if ($logger !== null) {
            $generator->setLogger($logger);
        }

        return $generator;
    }

    protected function makeLogger()
    {
        return new class {
            public array $messages = [];
            public function info($m) { $this->messages[] = ['info', $m]; }
            public function error($m) { $this->messages[] = ['error', $m]; }
            public function warn($m) { $this->messages[] = ['warn', $m]; }
        };
    }

    public function test_pushes_into_context_bucket()
    {
        $context = new PipelineContext(false);
        $generator = $this->makeGenerator($context);

        $generator->pushPackage('laravel/scout', '^10.0');

        $this->assertSame(['laravel/scout' => '^10.0'], $context->get('composer_requires'));
    }

    public function test_idempotent_on_identical_push()
    {
        $context = new PipelineContext(false);
        $generator = $this->makeGenerator($context);

        $generator->pushPackage('laravel/scout', '^10.0');
        $generator->pushPackage('laravel/scout', '^10.0');

        $this->assertSame(['laravel/scout' => '^10.0'], $context->get('composer_requires'));
    }

    public function test_concrete_overrides_existing_wildcard()
    {
        $context = new PipelineContext(false);
        $generator = $this->makeGenerator($context);

        $generator->pushPackage('laravel/scout', '*');
        $generator->pushPackage('laravel/scout', '^10.0');

        $this->assertSame(['laravel/scout' => '^10.0'], $context->get('composer_requires'));
    }

    public function test_wildcard_yields_to_existing_concrete()
    {
        $context = new PipelineContext(false);
        $generator = $this->makeGenerator($context);

        $generator->pushPackage('laravel/scout', '^10.0');
        $generator->pushPackage('laravel/scout', '*');

        $this->assertSame(['laravel/scout' => '^10.0'], $context->get('composer_requires'));
    }

    public function test_conflicting_concrete_pushes_keeps_first_and_warns()
    {
        $context = new PipelineContext(false);
        $logger = $this->makeLogger();
        $generator = $this->makeGenerator($context, $logger);

        $generator->pushPackage('laravel/scout', '^10.0');
        $generator->pushPackage('laravel/scout', '^9.0');

        $this->assertSame(['laravel/scout' => '^10.0'], $context->get('composer_requires'));

        $warnings = array_filter($logger->messages, fn($e) => $e[0] === 'warn' && str_contains($e[1], 'Conflicting generator requires for laravel/scout'));
        $this->assertNotEmpty($warnings);
    }
}
