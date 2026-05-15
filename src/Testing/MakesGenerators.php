<?php

namespace Firevel\Generator\Testing;

use Firevel\Generator\Generators\BaseGenerator;
use Firevel\Generator\Logging\NullLogger;
use Firevel\Generator\PipelineContext;
use Firevel\Generator\Resource;

/**
 * Test helper for extension authors writing generators.
 *
 * Builds a fully wired BaseGenerator (Resource + PipelineContext + NullLogger)
 * so tests can focus on the generator's own behavior, not scaffolding.
 *
 * Example:
 *
 *     use Firevel\Generator\Testing\MakesGenerators;
 *
 *     class MyGeneratorTest extends \PHPUnit\Framework\TestCase
 *     {
 *         use MakesGenerators;
 *
 *         public function test_writes_expected_file()
 *         {
 *             $generator = $this->makeGenerator(MyGenerator::class, [
 *                 'name' => 'Article',
 *             ]);
 *             $generator->handle();
 *
 *             $this->assertFileExists(base_path('app/Models/Article.php'));
 *         }
 *     }
 */
trait MakesGenerators
{
    /**
     * Instantiate a generator with a Resource (built from $attributes), a
     * PipelineContext (with optional $contextOverrides), and a NullLogger.
     */
    protected function makeGenerator(
        string $class,
        array $attributes = [],
        array $contextOverrides = []
    ): BaseGenerator {
        $resource = new Resource($attributes);
        $context = new PipelineContext(false);

        foreach ($contextOverrides as $key => $value) {
            $context->set($key, $value);
        }

        /** @var BaseGenerator $generator */
        $generator = new $class($resource, $context);
        $generator->setLogger($this->makeNullLogger());

        return $generator;
    }

    /**
     * Build a generator and immediately run handle(). Returns the generator
     * so tests can introspect its logger (NullLogger captures messages) or
     * context state.
     */
    protected function runGenerator(
        string $class,
        array $attributes = [],
        array $contextOverrides = []
    ): BaseGenerator {
        $generator = $this->makeGenerator($class, $attributes, $contextOverrides);
        $generator->handle();
        return $generator;
    }

    /**
     * Override in your test if you want to capture or inspect log messages
     * after the run (NullLogger keeps them in public arrays).
     */
    protected function makeNullLogger(): NullLogger
    {
        return new NullLogger();
    }
}
