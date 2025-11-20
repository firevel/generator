<?php

namespace Firevel\Generator\Tests\Generator\ApiResource;

use Orchestra\Testbench\Concerns\WithWorkbench;
use Firevel\Generator\Generators\ApiResource\MigrationsGenerator;
use Firevel\Generator\PipelineContext;
use Firevel\Generator\Resource;

class MigrationsGeneratorTest extends \Orchestra\Testbench\TestCase
{
    use WithWorkbench;

    protected function setUp(): void
    {
        parent::setUp();

        // Clean up any existing test migrations before each test
        $this->cleanupTestMigrations();
    }

    protected function tearDown(): void
    {
        // Clean up after tests
        $this->cleanupTestMigrations();

        parent::tearDown();
    }

    protected function cleanupTestMigrations()
    {
        $migrationsPath = database_path('migrations');
        $patterns = [
            '*_create_test_users_table.php',
            '*_create_products_table.php',
        ];

        foreach ($patterns as $pattern) {
            $files = glob($migrationsPath . '/' . $pattern);
            foreach ($files as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }
    }

    /** @test */
    public function test_creates_new_migration_when_none_exists()
    {
        $resource = new Resource([
            'name' => 'TestUser',
        ]);

        $context = new PipelineContext(false);
        $generator = new MigrationsGenerator($resource, $context);

        $logger = new class {
            public $messages = [];

            public function info($message) {
                $this->messages[] = ['type' => 'info', 'message' => $message];
            }

            public function error($message) {
                $this->messages[] = ['type' => 'error', 'message' => $message];
            }
        };

        $generator->setLogger($logger);
        $generator->handle();

        // Verify migration was created
        $migrationsPath = database_path('migrations');
        $migrations = glob($migrationsPath . '/*_create_test_users_table.php');

        $this->assertCount(1, $migrations, 'Expected one migration file to be created');
        $this->assertFileExists($migrations[0]);

        // Verify logger received creation message
        $creationMessages = array_filter($logger->messages, function($log) {
            return strpos($log['message'], 'Migration created:') !== false;
        });
        $this->assertCount(1, $creationMessages, 'Expected one migration creation log message');
    }

    /** @test */
    public function test_overwrites_existing_migration_by_default()
    {
        $resource = new Resource([
            'name' => 'Product',
        ]);

        $context = new PipelineContext(false);

        // Create initial migration
        $generator1 = new MigrationsGenerator($resource, $context);
        $logger1 = $this->createMockLogger();
        $generator1->setLogger($logger1);
        $generator1->handle();

        // Get the created migration file
        $migrationsPath = database_path('migrations');
        $migrations = glob($migrationsPath . '/*_create_products_table.php');
        $this->assertCount(1, $migrations);

        $originalFile = $migrations[0];
        $originalFilename = basename($originalFile);
        $originalContent = file_get_contents($originalFile);
        $originalMtime = filemtime($originalFile);

        // Wait a moment to ensure modification time would change
        sleep(1);

        // Run generator again (should overwrite)
        $generator2 = new MigrationsGenerator($resource, $context);
        $logger2 = $this->createMockLogger();
        $generator2->setLogger($logger2);
        $generator2->handle();

        // Verify only one migration file exists
        $migrationsAfter = glob($migrationsPath . '/*_create_products_table.php');
        $this->assertCount(1, $migrationsAfter, 'Should still have only one migration file');

        // Verify it's the same filename (not a new one with different timestamp)
        $this->assertEquals($originalFilename, basename($migrationsAfter[0]));

        // Verify the file was actually modified
        $newMtime = filemtime($migrationsAfter[0]);
        $this->assertGreaterThan($originalMtime, $newMtime, 'File should have been modified');

        // Verify logger received the override message
        $overwriteMessages = array_filter($logger2->messages, function($log) {
            return strpos($log['message'], 'Found existing migration:') !== false;
        });
        $this->assertCount(1, $overwriteMessages, 'Expected message about existing migration');

        $overwrittenMessages = array_filter($logger2->messages, function($log) {
            return strpos($log['message'], 'Migration overwritten:') !== false;
        });
        $this->assertCount(1, $overwrittenMessages, 'Expected message about overwriting migration');
    }

    /** @test */
    public function test_skips_migration_when_user_declines_override()
    {
        $resource = new Resource([
            'name' => 'Product',
        ]);

        $context = new PipelineContext(false);

        // Create initial migration
        $generator1 = new MigrationsGenerator($resource, $context);
        $logger1 = $this->createMockLogger();
        $generator1->setLogger($logger1);
        $generator1->handle();

        // Get the created migration
        $migrationsPath = database_path('migrations');
        $migrations = glob($migrationsPath . '/*_create_products_table.php');
        $this->assertCount(1, $migrations);
        $originalContent = file_get_contents($migrations[0]);
        $originalMtime = filemtime($migrations[0]);

        sleep(1);

        // Run generator again with logger that declines override
        $generator2 = new MigrationsGenerator($resource, $context);
        $logger2 = new class {
            public $messages = [];

            public function info($message) {
                $this->messages[] = ['type' => 'info', 'message' => $message];
            }

            public function error($message) {
                $this->messages[] = ['type' => 'error', 'message' => $message];
            }

            public function confirm($message, $default = true) {
                return false; // User declines override
            }
        };

        $generator2->setLogger($logger2);
        $generator2->handle();

        // Verify file was NOT modified
        $migrationsAfter = glob($migrationsPath . '/*_create_products_table.php');
        $this->assertCount(1, $migrationsAfter);
        $newContent = file_get_contents($migrationsAfter[0]);
        $newMtime = filemtime($migrationsAfter[0]);

        $this->assertEquals($originalContent, $newContent, 'Content should not have changed');
        $this->assertEquals($originalMtime, $newMtime, 'Modification time should not have changed');

        // Verify logger received skip message
        $skipMessages = array_filter($logger2->messages, function($log) {
            return strpos($log['message'], 'Skipped migration creation') !== false;
        });
        $this->assertCount(1, $skipMessages, 'Expected message about skipping migration');
    }

    /** @test */
    public function test_handles_multiple_existing_migrations_with_same_pattern()
    {
        $resource = new Resource([
            'name' => 'Product',
        ]);

        // Create two migrations manually (simulating old duplicates)
        $migrationsPath = database_path('migrations');
        $migration1 = $migrationsPath . '/2024_01_01_000000_create_products_table.php';
        $migration2 = $migrationsPath . '/2024_01_02_000000_create_products_table.php';

        file_put_contents($migration1, "<?php\n// Old migration 1");
        file_put_contents($migration2, "<?php\n// Old migration 2");

        $context = new PipelineContext(false);
        $generator = new MigrationsGenerator($resource, $context);
        $logger = $this->createMockLogger();
        $generator->setLogger($logger);
        $generator->handle();

        // Should overwrite the first one found
        $migrations = glob($migrationsPath . '/*_create_products_table.php');
        $this->assertCount(2, $migrations, 'Should still have both migrations');

        // First migration should be overwritten
        $content1 = file_get_contents($migration1);
        $this->assertStringNotContainsString('Old migration 1', $content1, 'First migration should be overwritten');

        // Second migration should remain unchanged
        $content2 = file_get_contents($migration2);
        $this->assertEquals("<?php\n// Old migration 2", $content2, 'Second migration should be unchanged');
    }

    protected function createMockLogger()
    {
        return new class {
            public $messages = [];

            public function info($message) {
                $this->messages[] = ['type' => 'info', 'message' => $message];
            }

            public function error($message) {
                $this->messages[] = ['type' => 'error', 'message' => $message];
            }
        };
    }

    protected function getPackageProviders($app)
    {
        return [
            \Firevel\Generator\ServiceProvider::class,
        ];
    }
}
