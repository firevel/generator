<?php

namespace Firevel\Generator\Tests\Generator\App;

use Orchestra\Testbench\Concerns\WithWorkbench;
use Firevel\Generator\Generators\App\EnvGenerator;
use Firevel\Generator\PipelineContext;
use Firevel\Generator\Resource;

class EnvGeneratorTest extends \Orchestra\Testbench\TestCase
{
    use WithWorkbench;

    protected $envPath;
    protected $envBackup;

    protected function setUp(): void
    {
        parent::setUp();

        // Get the .env path from base_path
        $this->envPath = base_path('.env');

        // Backup original .env if it exists
        if (file_exists($this->envPath)) {
            $this->envBackup = file_get_contents($this->envPath);
        }
    }

    protected function tearDown(): void
    {
        // Restore original .env or delete if it didn't exist
        if ($this->envBackup !== null) {
            file_put_contents($this->envPath, $this->envBackup);
        } elseif (file_exists($this->envPath)) {
            unlink($this->envPath);
        }

        parent::tearDown();
    }

    /** @test */
    public function test_adds_new_env_variables()
    {
        $input = new Resource([
            'env' => [
                'APP_NAME' => 'TestApp',
                'APP_ENV' => 'testing',
                'APP_DEBUG' => 'true',
            ],
        ]);

        $context = new PipelineContext(true);
        $context->set('input', $input);

        $resource = new Resource([]);
        $generator = new EnvGenerator($resource, $context);

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

        // Verify .env was created and contains the variables
        $this->assertFileExists($this->envPath);
        $envContent = file_get_contents($this->envPath);
        $this->assertStringContainsString('APP_NAME=TestApp', $envContent);
        $this->assertStringContainsString('APP_ENV=testing', $envContent);
        $this->assertStringContainsString('APP_DEBUG=true', $envContent);
    }

    /** @test */
    public function test_updates_existing_env_variables()
    {
        // Create initial .env file
        file_put_contents($this->envPath, "APP_NAME=OldApp\nAPP_ENV=production\n");

        $input = new Resource([
            'env' => [
                'APP_NAME' => 'NewApp',
                'APP_ENV' => 'staging',
            ],
        ]);

        $context = new PipelineContext(true);
        $context->set('input', $input);

        $resource = new Resource([]);
        $generator = new EnvGenerator($resource, $context);

        $logger = new class {
            public function info($message) {}
            public function error($message) {}
            public function warn($message) {}
            public function confirm($message, $default) { return true; }
        };

        $generator->setLogger($logger);
        $generator->handle();

        // Verify variables were updated
        $envContent = file_get_contents($this->envPath);
        $this->assertStringContainsString('APP_NAME=NewApp', $envContent);
        $this->assertStringContainsString('APP_ENV=staging', $envContent);
        $this->assertStringNotContainsString('OldApp', $envContent);
        $this->assertStringNotContainsString('production', $envContent);
    }

    /** @test */
    public function test_preserves_comments_and_empty_lines()
    {
        // Create .env with comments
        file_put_contents($this->envPath, "# Application settings\nAPP_NAME=TestApp\n\n# Database settings\nDB_HOST=localhost\n");

        $input = new Resource([
            'env' => [
                'APP_NAME' => 'UpdatedApp',
            ],
        ]);

        $context = new PipelineContext(true);
        $context->set('input', $input);

        $resource = new Resource([]);
        $generator = new EnvGenerator($resource, $context);

        $logger = new class {
            public function info($message) {}
            public function error($message) {}
            public function warn($message) {}
            public function confirm($message, $default) { return true; }
        };

        $generator->setLogger($logger);
        $generator->handle();

        // Verify comments are preserved
        $envContent = file_get_contents($this->envPath);
        $this->assertStringContainsString('# Application settings', $envContent);
        $this->assertStringContainsString('# Database settings', $envContent);
        $this->assertStringContainsString('DB_HOST=localhost', $envContent);
    }

    /** @test */
    public function test_formats_values_with_spaces()
    {
        $input = new Resource([
            'env' => [
                'APP_NAME' => 'My Application',
                'APP_URL' => 'http://localhost',
            ],
        ]);

        $context = new PipelineContext(true);
        $context->set('input', $input);

        $resource = new Resource([]);
        $generator = new EnvGenerator($resource, $context);

        $logger = new class {
            public function info($message) {}
            public function error($message) {}
            public function warn($message) {}
            public function confirm($message, $default) { return true; }
        };

        $generator->setLogger($logger);
        $generator->handle();

        // Verify values with spaces are quoted
        $envContent = file_get_contents($this->envPath);
        $this->assertStringContainsString('APP_NAME="My Application"', $envContent);
        $this->assertStringContainsString('APP_URL=http://localhost', $envContent);
    }

    /** @test */
    public function test_skips_silently_when_no_env_field()
    {
        $input = new Resource([
            'service' => [
                'name' => 'test-service',
            ],
        ]);

        $context = new PipelineContext(true);
        $context->set('input', $input);

        $resource = new Resource([]);
        $generator = new EnvGenerator($resource, $context);

        $logger = new class {
            public $messages = [];
            public function info($message) { $this->messages[] = $message; }
            public function error($message) { $this->messages[] = $message; }
            public function warn($message) { $this->messages[] = $message; }
        };

        $generator->setLogger($logger);
        $generator->handle();

        // Should not log any messages (silent skip)
        $this->assertEmpty($logger->messages);
    }

    /** @test */
    public function test_handles_empty_env_field()
    {
        $input = new Resource([
            'env' => [],
        ]);

        $context = new PipelineContext(true);
        $context->set('input', $input);

        $resource = new Resource([]);
        $generator = new EnvGenerator($resource, $context);

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
    public function test_skips_variable_with_same_value()
    {
        // Create initial .env file
        file_put_contents($this->envPath, "APP_NAME=TestApp\n");

        $input = new Resource([
            'env' => [
                'APP_NAME' => 'TestApp',
            ],
        ]);

        $context = new PipelineContext(true);
        $context->set('input', $input);

        $resource = new Resource([]);
        $generator = new EnvGenerator($resource, $context);

        $logger = new class {
            public $messages = [];
            public function info($message) { $this->messages[] = $message; }
            public function error($message) {}
            public function warn($message) {}
        };

        $generator->setLogger($logger);
        $generator->handle();

        // Verify message about existing variable
        $hasMessage = false;
        foreach ($logger->messages as $message) {
            if (str_contains($message, 'already exists with the same value')) {
                $hasMessage = true;
                break;
            }
        }
        $this->assertTrue($hasMessage, 'Should log that variable already exists with same value');
    }

    /** @test */
    public function test_adds_new_variables_to_existing_file()
    {
        // Create initial .env file with one variable
        file_put_contents($this->envPath, "APP_NAME=TestApp\n");

        $input = new Resource([
            'env' => [
                'APP_ENV' => 'testing',
                'APP_DEBUG' => 'true',
            ],
        ]);

        $context = new PipelineContext(true);
        $context->set('input', $input);

        $resource = new Resource([]);
        $generator = new EnvGenerator($resource, $context);

        $logger = new class {
            public function info($message) {}
            public function error($message) {}
            public function warn($message) {}
            public function confirm($message, $default) { return true; }
        };

        $generator->setLogger($logger);
        $generator->handle();

        // Verify new variables were added and old one preserved
        $envContent = file_get_contents($this->envPath);
        $this->assertStringContainsString('APP_NAME=TestApp', $envContent);
        $this->assertStringContainsString('APP_ENV=testing', $envContent);
        $this->assertStringContainsString('APP_DEBUG=true', $envContent);
    }

    /** @test */
    public function test_handles_boolean_values()
    {
        $input = new Resource([
            'env' => [
                'APP_DEBUG' => true,
                'MAIL_ENABLED' => false,
            ],
        ]);

        $context = new PipelineContext(true);
        $context->set('input', $input);

        $resource = new Resource([]);
        $generator = new EnvGenerator($resource, $context);

        $logger = new class {
            public function info($message) {}
            public function error($message) {}
            public function warn($message) {}
            public function confirm($message, $default) { return true; }
        };

        $generator->setLogger($logger);
        $generator->handle();

        // Verify boolean values are converted to strings
        $envContent = file_get_contents($this->envPath);
        $this->assertStringContainsString('APP_DEBUG=true', $envContent);
        $this->assertStringContainsString('MAIL_ENABLED=false', $envContent);
    }

    protected function getPackageProviders($app)
    {
        return [
            \Firevel\Generator\ServiceProvider::class,
        ];
    }
}
