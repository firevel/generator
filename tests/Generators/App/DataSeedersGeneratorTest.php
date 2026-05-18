<?php

namespace Firevel\Generator\Tests\Generators\App;

use Firevel\Generator\Generators\App\DataSeedersGenerator;
use Firevel\Generator\PipelineContext;
use Firevel\Generator\Resource;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;

class DataSeedersGeneratorTest extends TestCase
{
    use WithWorkbench;

    /** @var string[] */
    private array $written = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->written = [];
    }

    protected function tearDown(): void
    {
        foreach ($this->written as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
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
     * Run DataSeedersGenerator scoped to the seeders block (matches how the
     * 'seeders' pipeline runs in production) and return the freshly written
     * paths plus the emitted-class context value.
     *
     * @param array<string, mixed> $seeders
     * @return array{paths: array<string, string>, emitted: array<int, string>}
     */
    private function runGenerator(array $seeders): array
    {
        $context = new PipelineContext(true);
        $generator = new DataSeedersGenerator(new Resource($seeders), $context);
        $generator->setLogger($this->makeLogger());
        $generator->handle();

        $paths = [];
        foreach ($seeders as $name => $entries) {
            if (! is_array($entries) || $entries === []) {
                continue;
            }
            $className = \Illuminate\Support\Str::studly($name) . 'DataSeeder';
            $path = database_path('seeders') . '/' . $className . '.php';
            $paths[$className] = $path;
            $this->written[] = $path;
        }

        return [
            'paths' => $paths,
            'emitted' => $context->get('seeders.emitted_classes', []),
        ];
    }

    public function test_emits_one_file_per_non_empty_set(): void
    {
        $result = $this->runGenerator([
            'system' => [
                ['App\\Models\\Role' => ['name' => 'admin']],
            ],
            'demo' => [
                ['App\\Models\\User' => ['name' => 'john']],
            ],
            'empty' => [],
        ]);

        $this->assertSame(['SystemDataSeeder', 'DemoDataSeeder'], $result['emitted']);
        $this->assertFileExists($result['paths']['SystemDataSeeder']);
        $this->assertFileExists($result['paths']['DemoDataSeeder']);
        $this->assertArrayNotHasKey('EmptyDataSeeder', $result['paths']);
    }

    public function test_renders_simple_scalar_fields(): void
    {
        $result = $this->runGenerator([
            'system' => [
                ['App\\Models\\Role' => [
                    'name' => 'admin',
                    'description' => "it's fine",
                    'active' => true,
                    'priority' => 10,
                    'optional' => null,
                ]],
            ],
        ]);

        $source = file_get_contents($result['paths']['SystemDataSeeder']);

        $this->assertStringContainsString("\\App\\Models\\Role::insert([", $source);
        $this->assertStringContainsString("'name' => 'admin',", $source);
        $this->assertStringContainsString("'description' => 'it\\'s fine',", $source);
        $this->assertStringContainsString("'active' => true,", $source);
        $this->assertStringContainsString("'priority' => 10,", $source);
        $this->assertStringContainsString("'optional' => null,", $source);
        $this->assertStringContainsString('namespace Database\\Seeders;', $source);
        $this->assertStringContainsString('use Illuminate\\Database\\Seeder;', $source);
        $this->assertStringContainsString('class SystemDataSeeder extends Seeder', $source);
    }

    public function test_renders_list_field_as_php_array_literal(): void
    {
        $result = $this->runGenerator([
            'system' => [
                ['App\\Models\\Role' => [
                    'tags' => ['a', 'b', 'c'],
                    'counts' => [1, 2, 3],
                ]],
            ],
        ]);

        $source = file_get_contents($result['paths']['SystemDataSeeder']);

        $this->assertStringContainsString("'tags' => ['a', 'b', 'c'],", $source);
        $this->assertStringContainsString("'counts' => [1, 2, 3],", $source);
    }

    public function test_renders_no_arg_invocation(): void
    {
        $result = $this->runGenerator([
            'system' => [
                ['App\\Models\\Role' => [
                    'created_at' => ['Illuminate\\Support\\Carbon' => ['now' => null]],
                    'id' => ['Illuminate\\Support\\Str' => ['uuid' => null]],
                ]],
            ],
        ]);

        $source = file_get_contents($result['paths']['SystemDataSeeder']);

        $this->assertStringContainsString("'created_at' => \\Illuminate\\Support\\Carbon::now(),", $source);
        $this->assertStringContainsString("'id' => \\Illuminate\\Support\\Str::uuid(),", $source);
    }

    public function test_renders_single_scalar_arg_invocation(): void
    {
        $result = $this->runGenerator([
            'system' => [
                ['App\\Models\\User' => [
                    'password' => ['Illuminate\\Support\\Facades\\Hash' => ['make' => 'password1']],
                ]],
            ],
        ]);

        $source = file_get_contents($result['paths']['SystemDataSeeder']);

        $this->assertStringContainsString(
            "'password' => \\Illuminate\\Support\\Facades\\Hash::make('password1'),",
            $source
        );
    }

    public function test_renders_chained_invocation_for_fk_lookup(): void
    {
        $result = $this->runGenerator([
            'system' => [
                ['App\\Models\\User' => [
                    'role_id' => ['App\\Models\\Role' => [
                        'where' => ['name', 'admin'],
                        'value' => 'id',
                    ]],
                ]],
            ],
        ]);

        $source = file_get_contents($result['paths']['SystemDataSeeder']);

        $this->assertStringContainsString(
            "'role_id' => \\App\\Models\\Role::where('name', 'admin')->value('id'),",
            $source
        );
    }

    public function test_renders_chain_with_null_terminal_step(): void
    {
        // Role::where('name', 'admin')->first()
        $result = $this->runGenerator([
            'system' => [
                ['App\\Models\\User' => [
                    'role' => ['App\\Models\\Role' => [
                        'where' => ['name', 'admin'],
                        'first' => null,
                    ]],
                ]],
            ],
        ]);

        $source = file_get_contents($result['paths']['SystemDataSeeder']);

        $this->assertStringContainsString(
            "'role' => \\App\\Models\\Role::where('name', 'admin')->first(),",
            $source
        );
    }

    public function test_generated_seeder_is_valid_php(): void
    {
        $result = $this->runGenerator([
            'system' => [
                ['App\\Models\\Role' => [
                    'name' => 'admin',
                    'description' => "Full's access",
                    'tags' => ['x', 'y'],
                    'created_at' => ['Illuminate\\Support\\Carbon' => ['now' => null]],
                ]],
                ['App\\Models\\User' => [
                    'role_id' => ['App\\Models\\Role' => [
                        'where' => ['name', 'admin'],
                        'value' => 'id',
                    ]],
                    'password' => ['Illuminate\\Support\\Facades\\Hash' => ['make' => 'pw']],
                ]],
            ],
        ]);

        $source = file_get_contents($result['paths']['SystemDataSeeder']);

        try {
            token_get_all($source, TOKEN_PARSE);
            $valid = true;
            $error = null;
        } catch (\ParseError $e) {
            $valid = false;
            $error = $e->getMessage();
        }

        $this->assertTrue($valid, "Generated seeder has PHP syntax errors: {$error}\n\n{$source}");
    }

    public function test_skips_when_seeders_block_is_empty(): void
    {
        $result = $this->runGenerator([]);

        $this->assertSame([], $result['emitted']);
    }
}
