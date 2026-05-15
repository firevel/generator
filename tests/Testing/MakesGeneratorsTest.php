<?php

namespace Firevel\Generator\Tests\Testing;

use Firevel\Generator\Generators\BaseGenerator;
use Firevel\Generator\Logging\NullLogger;
use Firevel\Generator\Testing\MakesGenerators;
use PHPUnit\Framework\TestCase;

class MakesGeneratorsTest extends TestCase
{
    use MakesGenerators;

    public function test_make_generator_wires_resource_context_and_null_logger()
    {
        $generator = $this->makeGenerator(SpyGenerator::class, ['name' => 'Article']);

        $this->assertSame('Article', (string) $generator->resource()->name);
        $this->assertInstanceOf(NullLogger::class, $generator->logger());
        $this->assertFalse($generator->context()->isMetaPipeline());
    }

    public function test_context_overrides_are_applied()
    {
        $generator = $this->makeGenerator(SpyGenerator::class, [], [
            'dry_run' => true,
            'skip_existing' => true,
        ]);

        $this->assertTrue($generator->isDryRun());
        $this->assertTrue($generator->shouldSkipExisting());
    }

    public function test_run_generator_calls_handle()
    {
        $generator = $this->runGenerator(SpyGenerator::class, ['name' => 'Article']);

        $this->assertTrue($generator->context()->get('ran'));
        // SpyGenerator logs one info line so we can verify NullLogger capture.
        $this->assertSame(['handled Article'], $generator->logger()->info);
    }
}

class SpyGenerator extends BaseGenerator
{
    public function handle()
    {
        $this->logger->info('handled ' . $this->resource()->name);
        $this->context->set('ran', true);
    }
}
