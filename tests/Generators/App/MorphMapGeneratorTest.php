<?php

namespace Firevel\Generator\Tests\Generator\App;

use Firevel\Generator\Generators\App\MorphMapGenerator;
use Firevel\Generator\PipelineContext;
use Firevel\Generator\Resource;
use Orchestra\Testbench\Concerns\WithWorkbench;

class MorphMapGeneratorTest extends \Orchestra\Testbench\TestCase
{
    use WithWorkbench;

    protected ?string $providersBackup = null;
    protected string $providerPath;
    protected string $providersPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->providerPath = base_path('app/Providers/MorphMapServiceProvider.php');
        $this->providersPath = base_path('bootstrap/providers.php');

        if (file_exists($this->providersPath)) {
            $this->providersBackup = file_get_contents($this->providersPath);
        }

        if (file_exists($this->providerPath)) {
            unlink($this->providerPath);
        }
    }

    protected function tearDown(): void
    {
        if (file_exists($this->providerPath)) {
            unlink($this->providerPath);
        }

        if ($this->providersBackup !== null) {
            file_put_contents($this->providersPath, $this->providersBackup);
        }

        parent::tearDown();
    }

    protected function makeLogger()
    {
        return new class {
            public array $messages = [];
            public function info($m) { $this->messages[] = ['type' => 'info', 'message' => $m]; }
            public function error($m) { $this->messages[] = ['type' => 'error', 'message' => $m]; }
            public function warn($m) { $this->messages[] = ['type' => 'warn', 'message' => $m]; }
        };
    }

    protected function runWithInput(array $input): array
    {
        $context = new PipelineContext(true);
        $context->set('input', new Resource($input));

        $generator = new MorphMapGenerator(new Resource([]), $context);
        $logger = $this->makeLogger();
        $generator->setLogger($logger);
        $generator->handle();

        return $logger->messages;
    }

    /** @test */
    public function test_writes_provider_with_kebab_singular_aliases()
    {
        $this->runWithInput([
            'resources' => [
                ['name' => 'Posts'],
                ['name' => 'BlogTags'],
                ['name' => 'User'],
            ],
        ]);

        $this->assertFileExists($this->providerPath);

        $source = file_get_contents($this->providerPath);

        $this->assertStringContainsString('namespace App\\Providers;', $source);
        $this->assertStringContainsString('class MorphMapServiceProvider extends ServiceProvider', $source);
        $this->assertStringContainsString('Relation::morphMap([', $source);

        // Aliases: kebab-case singular; classes: studly singular under App\Models.
        $this->assertStringContainsString("'post' => \\App\\Models\\Post::class,", $source);
        $this->assertStringContainsString("'blog-tag' => \\App\\Models\\BlogTag::class,", $source);
        $this->assertStringContainsString("'user' => \\App\\Models\\User::class,", $source);
    }

    /** @test */
    public function test_aliases_are_sorted_for_deterministic_output()
    {
        $this->runWithInput([
            'resources' => [
                ['name' => 'Tag'],
                ['name' => 'Comment'],
                ['name' => 'Post'],
            ],
        ]);

        $source = file_get_contents($this->providerPath);

        $commentPos = strpos($source, "'comment' =>");
        $postPos = strpos($source, "'post' =>");
        $tagPos = strpos($source, "'tag' =>");

        $this->assertNotFalse($commentPos);
        $this->assertNotFalse($postPos);
        $this->assertNotFalse($tagPos);
        $this->assertLessThan($postPos, $commentPos);
        $this->assertLessThan($tagPos, $postPos);
    }

    /** @test */
    public function test_dedupes_when_two_resources_collapse_to_same_alias()
    {
        $this->runWithInput([
            'resources' => [
                ['name' => 'Posts'],
                ['name' => 'Post'],
            ],
        ]);

        $source = file_get_contents($this->providerPath);
        $this->assertSame(1, substr_count($source, "'post' =>"));
    }

    /** @test */
    public function test_skips_when_no_resources_present()
    {
        $this->runWithInput([]);

        $this->assertFileDoesNotExist($this->providerPath);

        if ($this->providersBackup !== null) {
            $this->assertSame($this->providersBackup, file_get_contents($this->providersPath));
        }
    }

    /** @test */
    public function test_skips_resources_without_names()
    {
        $this->runWithInput([
            'resources' => [
                ['name' => 'Post'],
                ['type' => 'something'],
                'not-an-array',
            ],
        ]);

        $source = file_get_contents($this->providerPath);
        $this->assertStringContainsString("'post' =>", $source);
        $this->assertSame(1, substr_count($source, '=> \\App\\Models\\'));
    }

    /** @test */
    public function test_registers_provider_in_bootstrap_providers_idempotently()
    {
        $this->runWithInput([
            'resources' => [
                ['name' => 'Post'],
            ],
        ]);

        $providers = file_get_contents($this->providersPath);
        $this->assertStringContainsString('App\\Providers\\MorphMapServiceProvider::class', $providers);
        $this->assertSame(1, substr_count($providers, 'MorphMapServiceProvider::class'));

        $messages = $this->runWithInput([
            'resources' => [
                ['name' => 'Post'],
                ['name' => 'Tag'],
            ],
        ]);

        $providersAfter = file_get_contents($this->providersPath);
        $this->assertSame(1, substr_count($providersAfter, 'MorphMapServiceProvider::class'));

        // Idempotent re-run: the only generator output should be the morph-map
        // refresh — no "already registered" notice and no make:provider call.
        $infoMessages = array_column($messages, 'message');
        $this->assertEmpty(array_filter($infoMessages, fn($m) => str_contains($m, 'already registered')));
        $this->assertNotEmpty(array_filter(
            $infoMessages,
            fn($m) => str_contains($m, 'morph map:')
        ));
    }

    /** @test */
    public function test_generated_provider_parses_as_php()
    {
        $this->runWithInput([
            'resources' => [
                ['name' => 'Post'],
                ['name' => 'Tag'],
            ],
        ]);

        $source = file_get_contents($this->providerPath);

        try {
            token_get_all($source, TOKEN_PARSE);
            $valid = true;
            $error = null;
        } catch (\ParseError $e) {
            $valid = false;
            $error = $e->getMessage();
        }

        $this->assertTrue($valid, "Generated provider has PHP syntax errors: {$error}");
    }
}
