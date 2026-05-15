<?php

namespace Firevel\Generator\Tests\Feature;

use Firevel\Generator\FirevelGeneratorManager;
use Firevel\Generator\Generators\BaseGenerator;
use Firevel\Generator\ServiceProvider;
use Orchestra\Testbench\TestCase;

class ChainedPipelineTest extends TestCase
{
    /** @var string Temp file the consumer pipeline writes the resource it received. */
    public static $consumerSpyPath;

    protected function getPackageProviders($app)
    {
        return [ServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();

        self::$consumerSpyPath = tempnam(sys_get_temp_dir(), 'chain_spy_') . '.json';

        $manager = $this->app->make(FirevelGeneratorManager::class);

        $manager->extend('test-emit', [
            'emit' => ChainEmittingGenerator::class,
        ]);

        $manager->extend('test-consume', [
            'consume' => ChainConsumingGenerator::class,
        ]);
    }

    protected function tearDown(): void
    {
        if (self::$consumerSpyPath && file_exists(self::$consumerSpyPath)) {
            unlink(self::$consumerSpyPath);
        }
        self::$consumerSpyPath = null;

        parent::tearDown();
    }

    /** @test */
    public function test_output_sentinel_feeds_first_pipeline_output_into_second()
    {
        $sourceJson = tempnam(sys_get_temp_dir(), 'chain_src_') . '.json';
        file_put_contents($sourceJson, json_encode(['name' => 'SeedResource']));

        try {
            $this->artisan('firevel:generate', [
                'pipeline' => 'test-emit,test-consume',
                '--json' => "{$sourceJson},@output",
            ])->assertExitCode(0);

            $consumed = json_decode(file_get_contents(self::$consumerSpyPath), true);

            $this->assertSame('SeedResource-piped', $consumed['name']);
            $this->assertSame('emitted-by-first', $consumed['marker']);
        } finally {
            if (file_exists($sourceJson)) {
                unlink($sourceJson);
            }
        }
    }

    /** @test */
    public function test_empty_json_slot_also_means_use_previous_output()
    {
        $sourceJson = tempnam(sys_get_temp_dir(), 'chain_src_') . '.json';
        file_put_contents($sourceJson, json_encode(['name' => 'AnotherSeed']));

        try {
            $this->artisan('firevel:generate', [
                'pipeline' => 'test-emit,test-consume',
                '--json' => "{$sourceJson},",
            ])->assertExitCode(0);

            $consumed = json_decode(file_get_contents(self::$consumerSpyPath), true);
            $this->assertSame('AnotherSeed-piped', $consumed['name']);
        } finally {
            if (file_exists($sourceJson)) {
                unlink($sourceJson);
            }
        }
    }

    /** @test */
    public function test_output_sentinel_without_a_prior_pipeline_emitting_errors_cleanly()
    {
        $this->artisan('firevel:generate', [
            'pipeline' => 'test-consume',
            '--json' => '@output',
        ])
        ->expectsOutputToContain('no previous pipeline emitted any')
        ->assertExitCode(0);

        $this->assertFalse(file_exists(self::$consumerSpyPath) && filesize(self::$consumerSpyPath) > 0);
    }

    /** @test */
    public function test_pipe_flag_autofills_output_for_chained_pipelines()
    {
        $sourceJson = tempnam(sys_get_temp_dir(), 'chain_src_') . '.json';
        file_put_contents($sourceJson, json_encode(['name' => 'PipedSeed']));

        try {
            $this->artisan('firevel:generate', [
                'pipeline' => 'test-emit,test-consume',
                '--json' => $sourceJson,
                '--pipe' => true,
            ])->assertExitCode(0);

            $consumed = json_decode(file_get_contents(self::$consumerSpyPath), true);
            $this->assertSame('PipedSeed-piped', $consumed['name']);
        } finally {
            if (file_exists($sourceJson)) {
                unlink($sourceJson);
            }
        }
    }

    /** @test */
    public function test_pipe_flag_yields_to_explicit_slot()
    {
        $firstJson = tempnam(sys_get_temp_dir(), 'chain_src_') . '.json';
        file_put_contents($firstJson, json_encode(['name' => 'FirstSeed']));

        // Override slot — its own JSON, not @output.
        $overrideJson = tempnam(sys_get_temp_dir(), 'chain_override_') . '.json';
        file_put_contents($overrideJson, json_encode(['name' => 'ExplicitOverride']));

        try {
            $this->artisan('firevel:generate', [
                'pipeline' => 'test-emit,test-consume',
                '--json' => "{$firstJson},{$overrideJson}",
                '--pipe' => true,
            ])->assertExitCode(0);

            $consumed = json_decode(file_get_contents(self::$consumerSpyPath), true);
            $this->assertSame('ExplicitOverride', $consumed['name']);
        } finally {
            if (file_exists($firstJson)) {
                unlink($firstJson);
            }
            if (file_exists($overrideJson)) {
                unlink($overrideJson);
            }
        }
    }

    /** @test */
    public function test_shared_context_carries_composer_requires_across_chained_pipelines()
    {
        // Emit pipeline pushes a composer require; consume pipeline asserts it
        // can read that require from the shared context.
        $sourceJson = tempnam(sys_get_temp_dir(), 'chain_src_') . '.json';
        file_put_contents($sourceJson, json_encode(['name' => 'Whatever']));

        try {
            $this->artisan('firevel:generate', [
                'pipeline' => 'test-emit,test-consume',
                '--json' => "{$sourceJson},@output",
            ])->assertExitCode(0);

            $consumed = json_decode(file_get_contents(self::$consumerSpyPath), true);
            $this->assertSame('1.2.3', $consumed['composer_requires']['acme/widget'] ?? null);
        } finally {
            if (file_exists($sourceJson)) {
                unlink($sourceJson);
            }
        }
    }
}

class ChainEmittingGenerator extends BaseGenerator
{
    public function handle()
    {
        $name = $this->resource()->get('name', 'Anonymous');

        $this->context()->set('composer_requires', array_merge(
            (array) $this->context()->get('composer_requires', []),
            ['acme/widget' => '1.2.3']
        ));

        $this->emitOutput([
            'name' => $name . '-piped',
            'marker' => 'emitted-by-first',
        ]);
    }
}

class ChainConsumingGenerator extends BaseGenerator
{
    public function handle()
    {
        $payload = $this->resource()->all();
        $payload['composer_requires'] = $this->context()->get('composer_requires', []);

        file_put_contents(ChainedPipelineTest::$consumerSpyPath, json_encode($payload));
    }
}
