<?php

namespace Firevel\Generator\Tests\Generator\App;

use Orchestra\Testbench\Concerns\WithWorkbench;
use Firevel\Generator\Generators\App\ComposerRequireGenerator;
use Firevel\Generator\PipelineContext;
use Firevel\Generator\Resource;

class ComposerRequireGeneratorTest extends \Orchestra\Testbench\TestCase
{
    use WithWorkbench;

    protected $composerPath;
    protected $composerBackup;

    protected function setUp(): void
    {
        parent::setUp();

        // Get the composer.json path from base_path
        $this->composerPath = base_path('composer.json');

        // Backup original composer.json
        if (file_exists($this->composerPath)) {
            $this->composerBackup = file_get_contents($this->composerPath);
        }
    }

    protected function tearDown(): void
    {
        // Restore original composer.json
        if ($this->composerBackup !== null) {
            file_put_contents($this->composerPath, $this->composerBackup);
        }

        parent::tearDown();
    }

    /** @test */
    public function test_adds_new_packages()
    {
        $input = new Resource([
            'require' => [
                'monolog/monolog' => '^3.0',
                'guzzlehttp/guzzle' => '^7.5',
            ],
        ]);

        $context = new PipelineContext(true);
        $context->set('input', $input);

        $resource = new Resource([]);
        $generator = new ComposerRequireGenerator($resource, $context);

        // Mock logger
        $logger = new class {
            public $messages = [];
            public function info($message) { $this->messages[] = $message; }
            public function error($message) { $this->messages[] = $message; }
            public function warn($message) { $this->messages[] = $message; }
            public function confirm($message, $default) { return true; }
        };

        $generator->setLogger($logger);
        $generator->handle();

        // Verify composer.json was updated
        $composer = json_decode(file_get_contents($this->composerPath), true);
        $this->assertArrayHasKey('monolog/monolog', $composer['require']);
        $this->assertArrayHasKey('guzzlehttp/guzzle', $composer['require']);
        $this->assertEquals('^3.0', $composer['require']['monolog/monolog']);
        $this->assertEquals('^7.5', $composer['require']['guzzlehttp/guzzle']);
    }

    /** @test */
    public function test_packages_are_sorted_alphabetically()
    {
        $input = new Resource([
            'require' => [
                'zzz/package' => '^1.0',
                'aaa/package' => '^2.0',
                'mmm/package' => '^3.0',
            ],
        ]);

        $context = new PipelineContext(true);
        $context->set('input', $input);

        $resource = new Resource([]);
        $generator = new ComposerRequireGenerator($resource, $context);

        $logger = new class {
            public function info($message) {}
            public function error($message) {}
            public function warn($message) {}
            public function confirm($message, $default) { return true; }
        };

        $generator->setLogger($logger);
        $generator->handle();

        // Verify packages are sorted
        $composer = json_decode(file_get_contents($this->composerPath), true);
        $requireKeys = array_keys($composer['require']);
        $sortedKeys = $requireKeys;
        sort($sortedKeys);

        $this->assertEquals($sortedKeys, $requireKeys, 'Packages should be sorted alphabetically');
    }

    /** @test */
    public function test_skips_silently_when_no_require_field()
    {
        $input = new Resource([
            'service' => [
                'name' => 'test-service',
            ],
            'resources' => [],
        ]);

        $context = new PipelineContext(true);
        $context->set('input', $input);

        $resource = new Resource([]);
        $generator = new ComposerRequireGenerator($resource, $context);

        $logger = new class {
            public $messages = [];
            public function info($message) { $this->messages[] = $message; }
            public function error($message) { $this->messages[] = $message; }
            public function warn($message) { $this->messages[] = $message; }
        };

        $generator->setLogger($logger);

        // Should not throw any errors
        $generator->handle();

        // Should not log any messages (silent skip)
        $this->assertEmpty($logger->messages);
    }

    /** @test */
    public function test_handles_empty_require_field()
    {
        $input = new Resource([
            'require' => [],
        ]);

        $context = new PipelineContext(true);
        $context->set('input', $input);

        $resource = new Resource([]);
        $generator = new ComposerRequireGenerator($resource, $context);

        $logger = new class {
            public $messages = [];
            public function info($message) { $this->messages[] = $message; }
            public function error($message) { $this->messages[] = $message; }
            public function warn($message) { $this->messages[] = $message; }
        };

        $generator->setLogger($logger);
        $generator->handle();

        // Should skip silently
        $this->assertEmpty($logger->messages);
    }

    /** @test */
    public function test_updates_existing_package_version()
    {
        // First, add a package
        $composer = json_decode(file_get_contents($this->composerPath), true);
        $composer['require']['test/package'] = '^1.0';
        file_put_contents($this->composerPath, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // Now update it
        $input = new Resource([
            'require' => [
                'test/package' => '^2.0',
            ],
        ]);

        $context = new PipelineContext(true);
        $context->set('input', $input);

        $resource = new Resource([]);
        $generator = new ComposerRequireGenerator($resource, $context);

        $logger = new class {
            public function info($message) {}
            public function error($message) {}
            public function warn($message) {}
            public function confirm($message, $default) { return true; }
        };

        $generator->setLogger($logger);
        $generator->handle();

        // Verify package was updated
        $updatedComposer = json_decode(file_get_contents($this->composerPath), true);
        $this->assertEquals('^2.0', $updatedComposer['require']['test/package']);
    }

    /** @test */
    public function test_skips_package_with_same_version()
    {
        // First, add a package
        $composer = json_decode(file_get_contents($this->composerPath), true);
        $composer['require']['test/package'] = '^1.0';
        file_put_contents($this->composerPath, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // Try to add the same version
        $input = new Resource([
            'require' => [
                'test/package' => '^1.0',
            ],
        ]);

        $context = new PipelineContext(true);
        $context->set('input', $input);

        $resource = new Resource([]);
        $generator = new ComposerRequireGenerator($resource, $context);

        $logger = new class {
            public $messages = [];
            public function info($message) { $this->messages[] = $message; }
            public function error($message) {}
            public function warn($message) {}
        };

        $generator->setLogger($logger);
        $generator->handle();

        // Verify package already exists message
        $hasMessage = false;
        foreach ($logger->messages as $message) {
            if (str_contains($message, 'already exists with version')) {
                $hasMessage = true;
                break;
            }
        }
        $this->assertTrue($hasMessage, 'Should log that package already exists with same version');
    }

    protected function getPackageProviders($app)
    {
        return [
            \Firevel\Generator\ServiceProvider::class,
        ];
    }
}
