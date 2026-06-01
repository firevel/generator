<?php

namespace Firevel\Generator\Tests\Generator\ApiResource;

use Orchestra\Testbench\Concerns\WithWorkbench;
use Firevel\Generator\Generators\ApiResource\RequestsGenerator;
use Firevel\Generator\Resource;

class RequestsGeneratorTest extends \Orchestra\Testbench\TestCase
{
    use WithWorkbench;

    /** @test */
    public function test_index()
    {
        $resource = new Resource([
            'name' => 'User',
        ]);
        $generator = new RequestsGenerator($resource);

        $this->assertStringContainsString('class IndexUsers ', $generator->generateSource('index', $resource));
    }

    /** @test */
    public function test_store()
    {
        $resource = new Resource([
            'name' => 'User',
        ]);
        $generator = new RequestsGenerator($resource);

        $this->assertStringContainsString('class StoreUser ', $generator->generateSource('store', $resource));
    }

    /** @test */
    public function test_destroy()
    {
        $resource = new Resource([
            'name' => 'User',
        ]);
        $generator = new RequestsGenerator($resource);

        $this->assertStringContainsString('class DestroyUser ', $generator->generateSource('destroy', $resource));
    }

    /** @test */
    public function test_show()
    {
        $resource = new Resource([
            'name' => 'User',
        ]);
        $generator = new RequestsGenerator($resource);

        $this->assertStringContainsString('class ShowUser ', $generator->generateSource('show', $resource));
    }

    /** @test */
    public function test_update()
    {
        $resource = new Resource([
            'name' => 'User',
        ]);
        $generator = new RequestsGenerator($resource);

        $this->assertStringContainsString('class UpdateUser ', $generator->generateSource('update', $resource));
    }

    /** @test */
    public function test_default_index_extends()
    {
        $resource = new Resource([
            'name' => 'User',
        ]);
        $generator = new RequestsGenerator($resource);

        $this->assertStringContainsString('extends FormRequest', $generator->generateSource('index', $resource));
    }

    /** @test */
    public function test_index_extends()
    {
        $resource = new Resource([
            'name' => 'User',
            'requests' => [
                'index' => [
                    'extends' => 'CustomRequest'
                ]
            ]
        ]);
        $generator = new RequestsGenerator($resource);

        $this->assertStringContainsString('extends CustomRequest', $generator->generateSource('index', $resource));
    }

    /** @test */
    public function test_store_extends()
    {
        $resource = new Resource([
            'name' => 'User',
            'requests' => [
                'store' => [
                    'extends' => 'CustomRequest'
                ]
            ]
        ]);
        $generator = new RequestsGenerator($resource);

        $this->assertStringContainsString('extends CustomRequest', $generator->generateSource('store', $resource));
    }

    /** @test */
    public function test_destroy_extends()
    {
        $resource = new Resource([
            'name' => 'User',
            'requests' => [
                'destroy' => [
                    'extends' => 'CustomRequest'
                ]
            ]
        ]);
        $generator = new RequestsGenerator($resource);

        $this->assertStringContainsString('extends CustomRequest', $generator->generateSource('destroy', $resource));
    }

    /** @test */
    public function test_show_extends()
    {
        $resource = new Resource([
            'name' => 'User',
            'requests' => [
                'show' => [
                    'extends' => 'CustomRequest'
                ]
            ]
        ]);
        $generator = new RequestsGenerator($resource);

        $this->assertStringContainsString('extends CustomRequest', $generator->generateSource('show', $resource));
    }

    /** @test */
    public function test_update_extends()
    {
        $resource = new Resource([
            'name' => 'User',
            'requests' => [
                'update' => [
                    'extends' => 'CustomRequest'
                ]
            ]
        ]);
        $generator = new RequestsGenerator($resource);

        $this->assertStringContainsString('extends CustomRequest', $generator->generateSource('update', $resource));
    }

    /** @test */
    public function test_store_renders_imports_and_use_trait(): void
    {
        $resource = new Resource([
            'name' => 'Post',
            'requests' => [
                'store' => [
                    'use' => ['HasUploads' => 'App\\Traits\\HasUploads'],
                    'imports' => ['Illuminate\\Validation\\Rule'],
                ],
            ],
        ]);
        $generator = new RequestsGenerator($resource);
        $source = $generator->generateSource('store', $resource);

        // Top-level imports — both `imports` and `use` values land here.
        $this->assertStringContainsString('use Illuminate\\Validation\\Rule;', $source);
        $this->assertStringContainsString('use App\\Traits\\HasUploads;', $source);

        // Class-body trait use anchored on the class declaration.
        $this->assertMatchesRegularExpression(
            '/class StorePost extends FormRequest\s*\{\s*\n\s*use HasUploads;/',
            $source
        );
    }

    /** @test */
    public function test_update_renders_only_its_own_action_use_block(): void
    {
        // Configure use for `store` only; `update` must NOT see it.
        $resource = new Resource([
            'name' => 'Post',
            'requests' => [
                'store' => [
                    'use' => ['HasUploads' => 'App\\Traits\\HasUploads'],
                ],
            ],
        ]);
        $generator = new RequestsGenerator($resource);
        $source = $generator->generateSource('update', $resource);

        $this->assertStringNotContainsString('HasUploads', $source);
    }

    /** @test */
    public function test_each_action_picks_up_its_own_use_independently(): void
    {
        // Same trait on three actions — each should render it in its own file.
        $resource = new Resource([
            'name' => 'Post',
            'requests' => [
                'index' => ['use' => ['HasAuth' => 'App\\Traits\\HasAuth']],
                'destroy' => ['use' => ['HasAuth' => 'App\\Traits\\HasAuth']],
                'show' => ['use' => ['HasAuth' => 'App\\Traits\\HasAuth']],
            ],
        ]);
        $generator = new RequestsGenerator($resource);

        foreach (['index', 'destroy', 'show'] as $action) {
            $source = $generator->generateSource($action, $resource);
            $this->assertStringContainsString('use App\\Traits\\HasAuth;', $source, "action {$action} top-level import missing");
            $this->assertStringContainsString('    use HasAuth;', $source, "action {$action} class-body trait use missing");
        }
    }

    /** @test */
    public function test_request_without_blocks_is_unchanged(): void
    {
        $resource = new Resource(['name' => 'User']);
        $generator = new RequestsGenerator($resource);
        $source = $generator->generateSource('store', $resource);

        // No traits should leak into the class body.
        $this->assertStringNotContainsString('    use ', $source);
    }

    /** @test */
    public function test_generated_request_is_valid_php(): void
    {
        $resource = new Resource([
            'name' => 'Post',
            'requests' => [
                'store' => [
                    'use' => ['HasUploads' => 'App\\Traits\\HasUploads'],
                    'imports' => ['Illuminate\\Validation\\Rule'],
                    'rules' => ['title' => 'required|string'],
                ],
            ],
        ]);
        $generator = new RequestsGenerator($resource);
        $source = $generator->generateSource('store', $resource);

        try {
            token_get_all($source, TOKEN_PARSE);
            $valid = true;
            $error = null;
        } catch (\ParseError $e) {
            $valid = false;
            $error = $e->getMessage();
        }

        $this->assertTrue($valid, "Generated request has PHP syntax errors: {$error}\n\n{$source}");
    }
}
