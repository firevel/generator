<?php

namespace Firevel\Generator\Tests\Generator\ApiResource;

use Firevel\Generator\Generators\ApiResource\ControllerGenerator;
use Firevel\Generator\PipelineContext;
use Firevel\Generator\Resource;
use Orchestra\Testbench\Concerns\WithWorkbench;

class ControllerGeneratorTest extends \Orchestra\Testbench\TestCase
{
    use WithWorkbench;

    private function render(array $attributes): string
    {
        $generator = new ControllerGenerator(new Resource($attributes));
        return $generator->generateSource();
    }

    private function makeLogger()
    {
        return new class {
            public function info($m) {}
            public function error($m) {}
            public function warn($m) {}
            public function confirm($m, $d = true) { return true; }
        };
    }

    public function test_controller_generator_pushes_firevel_api_require(): void
    {
        $controllerDir = app_path('Http/Controllers/Api');

        $context = new PipelineContext(true);

        $generator = new ControllerGenerator(new Resource(['name' => 'Post']), $context);
        $generator->setLogger($this->makeLogger());

        try {
            $generator->handle();

            $this->assertSame(
                ['firevel/api' => '^0.1'],
                $context->get('composer_requires')
            );
        } finally {
            if (is_dir($controllerDir)) {
                foreach (glob($controllerDir . '/*.php') as $f) {
                    unlink($f);
                }
            }
        }
    }

    public function test_renders_base_controller_without_optional_use_blocks(): void
    {
        $source = $this->render(['name' => 'Post']);

        $this->assertStringContainsString('class PostsController extends Controller', $source);
        // No extra trait line in the class body when controller.use is absent.
        $this->assertStringNotContainsString("    use ", $source);
    }

    public function test_renders_top_level_imports_from_controller_imports(): void
    {
        $source = $this->render([
            'name' => 'Post',
            'controller' => [
                'imports' => [
                    'App\\Services\\PaymentGateway',
                    'App\\Http\\Resources\\PostResource',
                ],
            ],
        ]);

        $this->assertStringContainsString('use App\\Services\\PaymentGateway;', $source);
        $this->assertStringContainsString('use App\\Http\\Resources\\PostResource;', $source);
    }

    public function test_renders_trait_at_top_and_inside_class_body_from_controller_use(): void
    {
        $source = $this->render([
            'name' => 'Post',
            'controller' => [
                'use' => [
                    'HasMiddleware' => 'Illuminate\\Routing\\Controllers\\HasMiddleware',
                ],
            ],
        ]);

        // Top-level import.
        $this->assertStringContainsString('use Illuminate\\Routing\\Controllers\\HasMiddleware;', $source);

        // Class-body trait use. Anchor on the class line + the indented `use`.
        $this->assertMatchesRegularExpression(
            '/class PostsController extends Controller\s*\{\s*\n\s*use HasMiddleware;/',
            $source
        );
    }

    public function test_renders_multiple_traits_each_on_own_line_in_class_body(): void
    {
        $source = $this->render([
            'name' => 'Post',
            'controller' => [
                'use' => [
                    'HasMiddleware' => 'Illuminate\\Routing\\Controllers\\HasMiddleware',
                    'CustomTrait' => 'App\\Traits\\CustomTrait',
                ],
            ],
        ]);

        $this->assertStringContainsString('use Illuminate\\Routing\\Controllers\\HasMiddleware;', $source);
        $this->assertStringContainsString('use App\\Traits\\CustomTrait;', $source);
        $this->assertStringContainsString('    use HasMiddleware;', $source);
        $this->assertStringContainsString('    use CustomTrait;', $source);
    }

    public function test_combines_use_and_imports(): void
    {
        $source = $this->render([
            'name' => 'Post',
            'controller' => [
                'use' => [
                    'HasMiddleware' => 'Illuminate\\Routing\\Controllers\\HasMiddleware',
                ],
                'imports' => [
                    'App\\Services\\PaymentGateway',
                ],
            ],
        ]);

        $this->assertStringContainsString('use App\\Services\\PaymentGateway;', $source);
        $this->assertStringContainsString('use Illuminate\\Routing\\Controllers\\HasMiddleware;', $source);
        $this->assertStringContainsString('    use HasMiddleware;', $source);
        // Imports don't add a class-body `use Trait;` line.
        $this->assertStringNotContainsString('    use PaymentGateway;', $source);
    }

    public function test_generated_controller_is_valid_php(): void
    {
        $source = $this->render([
            'name' => 'Post',
            'controller' => [
                'use' => [
                    'HasMiddleware' => 'Illuminate\\Routing\\Controllers\\HasMiddleware',
                ],
                'imports' => [
                    'App\\Services\\PaymentGateway',
                ],
            ],
        ]);

        try {
            token_get_all($source, TOKEN_PARSE);
            $valid = true;
            $error = null;
        } catch (\ParseError $e) {
            $valid = false;
            $error = $e->getMessage();
        }

        $this->assertTrue($valid, "Generated controller has PHP syntax errors: {$error}\n\n{$source}");
    }
}
