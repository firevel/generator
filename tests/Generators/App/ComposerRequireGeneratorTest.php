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
        $originalContent = json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents($this->composerPath, $originalContent);

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

        // Same-version requires should be a no-op: no logs about "already
        // exists", no rewrite of composer.json, and no manual follow-up step.
        foreach ($logger->messages as $message) {
            $this->assertStringNotContainsString('already exists', $message);
            $this->assertStringNotContainsString('added:', $message);
            $this->assertStringNotContainsString('updated:', $message);
        }
        $this->assertSame($originalContent, file_get_contents($this->composerPath));
        $this->assertEmpty($context->get('summary.manual_steps', []));
    }

    /** @test */
    public function test_collects_per_resource_requires()
    {
        $input = new Resource([
            'resources' => [
                ['name' => 'Post', 'require' => ['spatie/laravel-sluggable' => '^3.0']],
                ['name' => 'Tag',  'require' => ['league/csv' => '^9.0']],
            ],
        ]);

        $context = new PipelineContext(true);
        $context->set('input', $input);

        $generator = new ComposerRequireGenerator(new Resource([]), $context);
        $generator->setLogger($this->makeAcceptingLogger());
        $generator->handle();

        $composer = json_decode(file_get_contents($this->composerPath), true);
        $this->assertSame('^3.0', $composer['require']['spatie/laravel-sluggable']);
        $this->assertSame('^9.0', $composer['require']['league/csv']);
    }

    /** @test */
    public function test_collects_generator_pushed_requires_from_context()
    {
        $input = new Resource([]);

        $context = new PipelineContext(true);
        $context->set('input', $input);
        $context->set('composer_requires', ['laravel/scout' => '^10.0']);

        $generator = new ComposerRequireGenerator(new Resource([]), $context);
        $generator->setLogger($this->makeAcceptingLogger());
        $generator->handle();

        $composer = json_decode(file_get_contents($this->composerPath), true);
        $this->assertSame('^10.0', $composer['require']['laravel/scout']);
    }

    /** @test */
    public function test_app_level_wins_over_resource_and_generator()
    {
        $input = new Resource([
            'require' => ['acme/widget' => '^9.0'],
            'resources' => [
                ['name' => 'Post', 'require' => ['acme/widget' => '^8.0']],
            ],
        ]);

        $context = new PipelineContext(true);
        $context->set('input', $input);
        $context->set('composer_requires', ['acme/widget' => '^7.0']);

        $generator = new ComposerRequireGenerator(new Resource([]), $context);
        $generator->setLogger($this->makeAcceptingLogger());
        $generator->handle();

        $composer = json_decode(file_get_contents($this->composerPath), true);
        $this->assertSame('^9.0', $composer['require']['acme/widget']);
    }

    /** @test */
    public function test_resource_wins_over_generator()
    {
        $input = new Resource([
            'resources' => [
                ['name' => 'Post', 'require' => ['acme/widget' => '^8.0']],
            ],
        ]);

        $context = new PipelineContext(true);
        $context->set('input', $input);
        $context->set('composer_requires', ['acme/widget' => '^7.0']);

        $generator = new ComposerRequireGenerator(new Resource([]), $context);
        $generator->setLogger($this->makeAcceptingLogger());
        $generator->handle();

        $composer = json_decode(file_get_contents($this->composerPath), true);
        $this->assertSame('^8.0', $composer['require']['acme/widget']);
    }

    /** @test */
    public function test_wildcard_resource_defers_to_generator()
    {
        $input = new Resource([
            'resources' => [
                ['name' => 'Post', 'require' => ['spatie/laravel-sluggable' => '*']],
            ],
        ]);

        $context = new PipelineContext(true);
        $context->set('input', $input);
        $context->set('composer_requires', ['spatie/laravel-sluggable' => '^3.0']);

        $generator = new ComposerRequireGenerator(new Resource([]), $context);
        $generator->setLogger($this->makeAcceptingLogger());
        $generator->handle();

        $composer = json_decode(file_get_contents($this->composerPath), true);
        $this->assertSame('^3.0', $composer['require']['spatie/laravel-sluggable']);
    }

    /** @test */
    public function test_wildcard_app_defers_to_generator()
    {
        $input = new Resource([
            'require' => ['laravel/scout' => '*'],
        ]);

        $context = new PipelineContext(true);
        $context->set('input', $input);
        $context->set('composer_requires', ['laravel/scout' => '^10.0']);

        $generator = new ComposerRequireGenerator(new Resource([]), $context);
        $generator->setLogger($this->makeAcceptingLogger());
        $generator->handle();

        $composer = json_decode(file_get_contents($this->composerPath), true);
        $this->assertSame('^10.0', $composer['require']['laravel/scout']);
    }

    /** @test */
    public function test_concrete_app_beats_wildcard_resource_even_when_generator_has_concrete()
    {
        $input = new Resource([
            'require' => ['acme/widget' => '^9.0'],
            'resources' => [
                ['name' => 'Post', 'require' => ['acme/widget' => '*']],
            ],
        ]);

        $context = new PipelineContext(true);
        $context->set('input', $input);
        $context->set('composer_requires', ['acme/widget' => '^10.0']);

        $generator = new ComposerRequireGenerator(new Resource([]), $context);
        $generator->setLogger($this->makeAcceptingLogger());
        $generator->handle();

        $composer = json_decode(file_get_contents($this->composerPath), true);
        $this->assertSame('^9.0', $composer['require']['acme/widget']);
    }

    /** @test */
    public function test_all_wildcards_falls_back_to_star_with_warning()
    {
        $input = new Resource([
            'require' => ['acme/widget' => '*'],
            'resources' => [
                ['name' => 'Post', 'require' => ['acme/widget' => '*']],
            ],
        ]);

        $context = new PipelineContext(true);
        $context->set('input', $input);
        $context->set('composer_requires', ['acme/widget' => '*']);

        $logger = new class {
            public array $messages = [];
            public function info($m) { $this->messages[] = ['info', $m]; }
            public function error($m) { $this->messages[] = ['error', $m]; }
            public function warn($m) { $this->messages[] = ['warn', $m]; }
            public function confirm($m, $d = true) { return true; }
        };

        $generator = new ComposerRequireGenerator(new Resource([]), $context);
        $generator->setLogger($logger);
        $generator->handle();

        $composer = json_decode(file_get_contents($this->composerPath), true);
        $this->assertSame('*', $composer['require']['acme/widget']);

        $warnings = array_filter($logger->messages, fn($e) => $e[0] === 'warn' && str_contains($e[1], "No concrete version found for acme/widget"));
        $this->assertNotEmpty($warnings, 'Expected a fallback warning when no concrete version is declared');
    }

    /** @test */
    public function test_conflicting_resource_requires_keeps_first_and_warns()
    {
        $input = new Resource([
            'resources' => [
                ['name' => 'Post', 'require' => ['acme/widget' => '^8.0']],
                ['name' => 'Tag',  'require' => ['acme/widget' => '^9.0']],
            ],
        ]);

        $context = new PipelineContext(true);
        $context->set('input', $input);

        $logger = new class {
            public array $messages = [];
            public function info($m) { $this->messages[] = ['info', $m]; }
            public function error($m) { $this->messages[] = ['error', $m]; }
            public function warn($m) { $this->messages[] = ['warn', $m]; }
            public function confirm($m, $d = true) { return true; }
        };

        $generator = new ComposerRequireGenerator(new Resource([]), $context);
        $generator->setLogger($logger);
        $generator->handle();

        $composer = json_decode(file_get_contents($this->composerPath), true);
        $this->assertSame('^8.0', $composer['require']['acme/widget']);

        $warnings = array_filter($logger->messages, fn($e) => $e[0] === 'warn' && str_contains($e[1], 'Conflicting resource-level requires'));
        $this->assertNotEmpty($warnings);
    }

    /** @test */
    public function test_star_package_is_installed_when_composer_available_and_user_confirms()
    {
        $input = new Resource([
            'resources' => [
                ['name' => 'Post', 'require' => ['spatie/laravel-fractal' => '*']],
            ],
        ]);

        $context = new PipelineContext(true);
        $context->set('input', $input);

        $generator = $this->makeInstallStubbedGenerator(
            new Resource([]),
            $context,
            composerAvailable: true,
            confirms: true,
            installedVersions: ['spatie/laravel-fractal' => '^6.0'],
            installSucceeds: true,
        );

        $generator->setLogger($this->makeAcceptingLogger());
        $generator->handle();

        $composer = json_decode(file_get_contents($this->composerPath), true);
        $this->assertSame('^6.0', $composer['require']['spatie/laravel-fractal']);
        $this->assertSame(['spatie/laravel-fractal'], $generator->installed);
    }

    /** @test */
    public function test_star_falls_back_when_composer_not_available()
    {
        $input = new Resource([
            'resources' => [
                ['name' => 'Post', 'require' => ['spatie/laravel-fractal' => '*']],
            ],
        ]);

        $context = new PipelineContext(true);
        $context->set('input', $input);

        $generator = $this->makeInstallStubbedGenerator(
            new Resource([]),
            $context,
            composerAvailable: false,
        );

        $generator->setLogger($this->makeAcceptingLogger());
        $generator->handle();

        $composer = json_decode(file_get_contents($this->composerPath), true);
        $this->assertSame('*', $composer['require']['spatie/laravel-fractal']);
        $this->assertEmpty($generator->installed);
    }

    /** @test */
    public function test_star_falls_back_when_user_declines()
    {
        $input = new Resource([
            'resources' => [
                ['name' => 'Post', 'require' => ['spatie/laravel-fractal' => '*']],
            ],
        ]);

        $context = new PipelineContext(true);
        $context->set('input', $input);

        $generator = $this->makeInstallStubbedGenerator(
            new Resource([]),
            $context,
            composerAvailable: true,
            confirms: false,
        );

        $generator->setLogger($this->makeAcceptingLogger());
        $generator->handle();

        $composer = json_decode(file_get_contents($this->composerPath), true);
        $this->assertSame('*', $composer['require']['spatie/laravel-fractal']);
        $this->assertEmpty($generator->installed);
    }

    /** @test */
    public function test_star_falls_back_when_composer_require_fails()
    {
        $input = new Resource([
            'resources' => [
                ['name' => 'Post', 'require' => ['spatie/laravel-fractal' => '*']],
            ],
        ]);

        $context = new PipelineContext(true);
        $context->set('input', $input);

        $generator = $this->makeInstallStubbedGenerator(
            new Resource([]),
            $context,
            composerAvailable: true,
            confirms: true,
            installSucceeds: false,
        );

        $logger = new class {
            public array $messages = [];
            public function info($m) { $this->messages[] = ['info', $m]; }
            public function error($m) { $this->messages[] = ['error', $m]; }
            public function warn($m) { $this->messages[] = ['warn', $m]; }
            public function confirm($m, $d = true) { return true; }
        };

        $generator->setLogger($logger);
        $generator->handle();

        $composer = json_decode(file_get_contents($this->composerPath), true);
        $this->assertSame('*', $composer['require']['spatie/laravel-fractal']);

        $warnings = array_filter($logger->messages, fn($e) => $e[0] === 'warn' && str_contains($e[1], 'failed'));
        $this->assertNotEmpty($warnings);
    }

    /** @test */
    public function test_install_only_runs_for_star_packages_not_concrete()
    {
        $input = new Resource([
            'require' => ['acme/widget' => '^9.0'],
            'resources' => [
                ['name' => 'Post', 'require' => ['spatie/laravel-fractal' => '*']],
            ],
        ]);

        $context = new PipelineContext(true);
        $context->set('input', $input);

        $generator = $this->makeInstallStubbedGenerator(
            new Resource([]),
            $context,
            composerAvailable: true,
            confirms: true,
            installedVersions: ['spatie/laravel-fractal' => '^6.0'],
            installSucceeds: true,
        );

        $generator->setLogger($this->makeAcceptingLogger());
        $generator->handle();

        $this->assertSame(['spatie/laravel-fractal'], $generator->installed,
            'Only the star-resolved package should trigger composer require');

        $composer = json_decode(file_get_contents($this->composerPath), true);
        $this->assertSame('^9.0', $composer['require']['acme/widget']);
        $this->assertSame('^6.0', $composer['require']['spatie/laravel-fractal']);
    }

    /** @test */
    public function test_skips_install_when_logger_has_no_confirm_method()
    {
        $input = new Resource([
            'resources' => [
                ['name' => 'Post', 'require' => ['spatie/laravel-fractal' => '*']],
            ],
        ]);

        $context = new PipelineContext(true);
        $context->set('input', $input);

        // Subclass that stubs ONLY the subprocess-touching methods — leaves confirmInstall()
        // intact so we actually exercise the real `method_exists($logger, 'confirm')` check.
        $generator = new class(new Resource([]), $context) extends ComposerRequireGenerator {
            public array $installed = [];
            protected function isComposerAvailable(): bool { return true; }
            protected function runComposerRequire(string $package): void { $this->installed[] = $package; }
            protected function readInstalledVersion(string $package): ?string { return '^6.0'; }
        };

        // Logger with no confirm() — simulates non-interactive runs.
        $generator->setLogger(new class {
            public function info($m) {}
            public function error($m) {}
            public function warn($m) {}
        });
        $generator->handle();

        $composer = json_decode(file_get_contents($this->composerPath), true);
        $this->assertSame('*', $composer['require']['spatie/laravel-fractal']);
        $this->assertEmpty($generator->installed);
    }

    protected function makeInstallStubbedGenerator(
        Resource $resource,
        PipelineContext $context,
        bool $composerAvailable = false,
        bool $confirms = false,
        array $installedVersions = [],
        bool $installSucceeds = true,
    ) {
        return new class($resource, $context, $composerAvailable, $confirms, $installedVersions, $installSucceeds) extends ComposerRequireGenerator {
            public array $installed = [];
            private bool $_available;
            private bool $_confirms;
            private array $_installedVersions;
            private bool $_succeeds;

            public function __construct(Resource $resource, PipelineContext $context, bool $available, bool $confirms, array $installedVersions, bool $succeeds)
            {
                parent::__construct($resource, $context);
                $this->_available = $available;
                $this->_confirms = $confirms;
                $this->_installedVersions = $installedVersions;
                $this->_succeeds = $succeeds;
            }

            protected function isComposerAvailable(): bool
            {
                return $this->_available;
            }

            protected function confirmInstall(string $package): bool
            {
                return $this->_confirms;
            }

            protected function runComposerRequire(string $package): void
            {
                $this->installed[] = $package;
                if (!$this->_succeeds) {
                    throw new \RuntimeException('simulated composer require failure');
                }
            }

            protected function readInstalledVersion(string $package): ?string
            {
                return $this->_installedVersions[$package] ?? null;
            }
        };
    }

    protected function makeAcceptingLogger()
    {
        return new class {
            public function info($m) {}
            public function error($m) {}
            public function warn($m) {}
            public function confirm($m, $d = true) { return true; }
        };
    }

    protected function getPackageProviders($app)
    {
        return [
            \Firevel\Generator\ServiceProvider::class,
        ];
    }
}
