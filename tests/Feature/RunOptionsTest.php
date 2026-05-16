<?php

namespace Firevel\Generator\Tests\Feature;

use Firevel\Generator\FirevelGeneratorManager;
use Firevel\Generator\Generators\BaseGenerator;
use Firevel\Generator\ServiceProvider;
use Orchestra\Testbench\TestCase;

class RunOptionsTest extends TestCase
{
    /** @var string Temp file generators write to. */
    public static $writePath;

    protected function getPackageProviders($app)
    {
        return [ServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();

        self::$writePath = sys_get_temp_dir() . '/run_options_target_' . uniqid() . '.txt';

        $manager = $this->app->make(FirevelGeneratorManager::class);

        $manager->extend('test-write', [
            'write' => WritingGenerator::class,
        ]);

        $manager->extend('test-update', [
            'update' => UpdatingGenerator::class,
        ]);

        $manager->extend('test-fail', [
            'first-ok' => WritingGenerator::class,
            'boom' => FailingGenerator::class,
            'never-runs' => WritingGenerator::class,
        ]);
    }

    protected function tearDown(): void
    {
        if (self::$writePath && file_exists(self::$writePath)) {
            unlink(self::$writePath);
        }
        self::$writePath = null;

        parent::tearDown();
    }

    /** @test */
    public function test_dry_run_does_not_write_files()
    {
        $this->assertFileDoesNotExist(self::$writePath);

        $this->artisan('firevel:generate', [
            'pipeline' => 'test-write',
            '--dry-run' => true,
        ])
        ->expectsOutputToContain('Dry-run mode')
        ->expectsOutputToContain('[dry-run] created')
        ->assertExitCode(0);

        $this->assertFileDoesNotExist(self::$writePath);
    }

    /** @test */
    public function test_dry_run_logs_overwrite_when_target_exists()
    {
        file_put_contents(self::$writePath, 'existing');

        $this->artisan('firevel:generate', [
            'pipeline' => 'test-write',
            '--dry-run' => true,
        ])
        ->expectsOutputToContain('[dry-run] overwrote')
        ->assertExitCode(0);

        $this->assertSame('existing', file_get_contents(self::$writePath));
    }

    /** @test */
    public function test_skip_existing_preserves_file()
    {
        file_put_contents(self::$writePath, 'hand-edited');

        $this->artisan('firevel:generate', [
            'pipeline' => 'test-write',
            '--skip-existing' => true,
        ])
        ->expectsOutputToContain('(exists)')
        ->assertExitCode(0);

        $this->assertSame('hand-edited', file_get_contents(self::$writePath));
    }

    /** @test */
    public function test_skip_existing_still_creates_missing_files()
    {
        $this->assertFileDoesNotExist(self::$writePath);

        $this->artisan('firevel:generate', [
            'pipeline' => 'test-write',
            '--skip-existing' => true,
        ])->assertExitCode(0);

        $this->assertSame('generated-content', file_get_contents(self::$writePath));
    }

    /** @test */
    public function test_update_file_ignores_skip_existing_but_honors_dry_run()
    {
        // updateFile() is for in-place updates (.env, composer.json) — must
        // still touch the file even when --skip-existing is set, because
        // the whole point is to modify it.
        file_put_contents(self::$writePath, 'before-update');

        $this->artisan('firevel:generate', [
            'pipeline' => 'test-update',
            '--skip-existing' => true,
        ])->assertExitCode(0);

        $this->assertSame('after-update', file_get_contents(self::$writePath));

        // But dry-run still gates it.
        file_put_contents(self::$writePath, 'before-update-2');

        $this->artisan('firevel:generate', [
            'pipeline' => 'test-update',
            '--dry-run' => true,
        ])
        ->expectsOutputToContain('[dry-run] updated')
        ->assertExitCode(0);

        $this->assertSame('before-update-2', file_get_contents(self::$writePath));
    }

    /** @test */
    public function test_failing_step_aborts_pipeline_with_step_name_and_nonzero_exit()
    {
        $this->assertFileDoesNotExist(self::$writePath);

        $exit = \Illuminate\Support\Facades\Artisan::call('firevel:generate', [
            'pipeline' => 'test-fail',
        ]);
        $output = \Illuminate\Support\Facades\Artisan::output();

        $this->assertSame(1, $exit);
        $this->assertStringContainsString("Pipeline 'test-fail' failed", $output);
        $this->assertStringContainsString("Step 'boom'", $output);
        $this->assertStringContainsString('intentional explosion', $output);

        // The first step ran before the failure (state is not rolled back).
        $this->assertFileExists(self::$writePath);
    }

    /** @test */
    public function test_failure_in_first_pipeline_aborts_the_rest_of_the_chain()
    {
        // The first pipeline (test-fail) has a writing step that runs before
        // the failing step. We capture how many writes happened. If the second
        // pipeline (test-write) ran, the file would also exist — but we want
        // to assert "exit 1, chain aborted." The first-pipeline write is fine.
        $exit = \Illuminate\Support\Facades\Artisan::call('firevel:generate', [
            'pipeline' => 'test-fail,test-write',
        ]);

        $this->assertSame(1, $exit);
        $output = \Illuminate\Support\Facades\Artisan::output();
        $this->assertStringContainsString("Pipeline 'test-fail' failed", $output);
        // The second pipeline never ran.
        $this->assertStringNotContainsString("test-write", $output);
    }
}

class WritingGenerator extends BaseGenerator
{
    public function handle()
    {
        $this->createFile(RunOptionsTest::$writePath, 'generated-content');
    }
}

class UpdatingGenerator extends BaseGenerator
{
    public function handle()
    {
        $this->updateFile(RunOptionsTest::$writePath, 'after-update');
    }
}

class FailingGenerator extends BaseGenerator
{
    public function handle()
    {
        throw new \RuntimeException('intentional explosion');
    }
}
