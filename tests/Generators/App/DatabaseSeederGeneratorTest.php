<?php

namespace Firevel\Generator\Tests\Generators\App;

use Firevel\Generator\Generators\App\DatabaseSeederGenerator;
use Firevel\Generator\PipelineContext;
use Firevel\Generator\Resource;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;

class DatabaseSeederGeneratorTest extends TestCase
{
    use WithWorkbench;

    private string $databaseSeederPath;
    private ?string $backup = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->databaseSeederPath = database_path('seeders') . '/DatabaseSeeder.php';
        if (file_exists($this->databaseSeederPath)) {
            $this->backup = file_get_contents($this->databaseSeederPath);
        }
    }

    protected function tearDown(): void
    {
        if ($this->backup !== null) {
            file_put_contents($this->databaseSeederPath, $this->backup);
        } elseif (file_exists($this->databaseSeederPath)) {
            unlink($this->databaseSeederPath);
        }
        parent::tearDown();
    }

    private function makeLogger()
    {
        return new class {
            public array $messages = [];
            public function info($m) { $this->messages[] = $m; }
            public function error($m) { $this->messages[] = $m; }
            public function warn($m) { $this->messages[] = $m; }
        };
    }

    /**
     * @param array<int, string>|null $emitted
     */
    private function runGenerator(?array $emitted): void
    {
        $context = new PipelineContext(true);
        if ($emitted !== null) {
            $context->set('seeders.emitted_classes', $emitted);
        }

        $generator = new DatabaseSeederGenerator(new Resource([]), $context);
        $generator->setLogger($this->makeLogger());
        $generator->handle();
    }

    public function test_writes_database_seeder_calling_each_emitted_class(): void
    {
        $this->runGenerator(['SystemDataSeeder', 'DemoDataSeeder']);

        $this->assertFileExists($this->databaseSeederPath);
        $source = file_get_contents($this->databaseSeederPath);

        $this->assertStringContainsString('namespace Database\\Seeders;', $source);
        $this->assertStringContainsString('class DatabaseSeeder extends Seeder', $source);
        $this->assertStringContainsString('$this->call(SystemDataSeeder::class);', $source);
        $this->assertStringContainsString('$this->call(DemoDataSeeder::class);', $source);

        // Order preserved.
        $sys = strpos($source, 'SystemDataSeeder::class');
        $demo = strpos($source, 'DemoDataSeeder::class');
        $this->assertNotFalse($sys);
        $this->assertNotFalse($demo);
        $this->assertLessThan($demo, $sys);
    }

    public function test_emits_only_actually_generated_classes(): void
    {
        $this->runGenerator(['SystemDataSeeder']);

        $source = file_get_contents($this->databaseSeederPath);
        $this->assertStringContainsString('$this->call(SystemDataSeeder::class);', $source);
        $this->assertStringNotContainsString('DemoDataSeeder', $source);
    }

    public function test_no_op_when_no_classes_were_emitted(): void
    {
        // Capture pre-state; the generator should not touch the file.
        $preExisted = file_exists($this->databaseSeederPath);
        $preContent = $preExisted ? file_get_contents($this->databaseSeederPath) : null;

        $this->runGenerator([]);

        if ($preExisted) {
            $this->assertSame($preContent, file_get_contents($this->databaseSeederPath));
        } else {
            $this->assertFileDoesNotExist($this->databaseSeederPath);
        }
    }

    public function test_generated_database_seeder_is_valid_php(): void
    {
        $this->runGenerator(['SystemDataSeeder', 'DemoDataSeeder']);

        $source = file_get_contents($this->databaseSeederPath);
        try {
            token_get_all($source, TOKEN_PARSE);
            $valid = true;
            $error = null;
        } catch (\ParseError $e) {
            $valid = false;
            $error = $e->getMessage();
        }

        $this->assertTrue($valid, "DatabaseSeeder has PHP syntax errors: {$error}");
    }
}
