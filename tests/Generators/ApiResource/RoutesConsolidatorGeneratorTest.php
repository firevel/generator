<?php

namespace Firevel\Generator\Tests\Generator\ApiResource;

use Firevel\Generator\Generators\ApiResource\RoutesConsolidatorGenerator;
use Firevel\Generator\PipelineContext;
use Firevel\Generator\Resource;
use Orchestra\Testbench\TestCase;
use Mockery;

class RoutesConsolidatorGeneratorTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function test_has_actual_code_detects_empty_file()
    {
        $context = new PipelineContext(true);
        $resource = new Resource([]);
        $generator = new RoutesConsolidatorGenerator($resource, $context);

        // Create a temporary empty file
        $tempFile = tempnam(sys_get_temp_dir(), 'test_empty_');
        file_put_contents($tempFile, '<?php');

        $reflection = new \ReflectionClass($generator);
        $method = $reflection->getMethod('hasActualCode');
        $method->setAccessible(true);

        $result = $method->invoke($generator, $tempFile);

        unlink($tempFile);

        $this->assertFalse($result);
    }

    /** @test */
    public function test_has_actual_code_detects_file_with_only_comments()
    {
        $context = new PipelineContext(true);
        $resource = new Resource([]);
        $generator = new RoutesConsolidatorGenerator($resource, $context);

        // Create a temporary file with only comments
        $tempFile = tempnam(sys_get_temp_dir(), 'test_comments_');
        file_put_contents($tempFile, "<?php\n\n// This is a comment\n/* Multi-line\ncomment */");

        $reflection = new \ReflectionClass($generator);
        $method = $reflection->getMethod('hasActualCode');
        $method->setAccessible(true);

        $result = $method->invoke($generator, $tempFile);

        unlink($tempFile);

        $this->assertFalse($result);
    }

    /** @test */
    public function test_has_actual_code_detects_file_with_only_use_statements()
    {
        $context = new PipelineContext(true);
        $resource = new Resource([]);
        $generator = new RoutesConsolidatorGenerator($resource, $context);

        // Create a temporary file with only use statements
        $tempFile = tempnam(sys_get_temp_dir(), 'test_uses_');
        file_put_contents($tempFile, "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\nuse App\\Http\\Controllers\\HomeController;");

        $reflection = new \ReflectionClass($generator);
        $method = $reflection->getMethod('hasActualCode');
        $method->setAccessible(true);

        $result = $method->invoke($generator, $tempFile);

        unlink($tempFile);

        $this->assertFalse($result);
    }

    /** @test */
    public function test_has_actual_code_detects_file_with_actual_code()
    {
        $context = new PipelineContext(true);
        $resource = new Resource([]);
        $generator = new RoutesConsolidatorGenerator($resource, $context);

        // Create a temporary file with actual code
        $tempFile = tempnam(sys_get_temp_dir(), 'test_code_');
        file_put_contents($tempFile, "<?php\n\nRoute::get('/', function() { return 'hello'; });");

        $reflection = new \ReflectionClass($generator);
        $method = $reflection->getMethod('hasActualCode');
        $method->setAccessible(true);

        $result = $method->invoke($generator, $tempFile);

        unlink($tempFile);

        $this->assertTrue($result);
    }

    /** @test */
    public function test_no_routes_logs_message()
    {
        $context = new PipelineContext(true);
        $resource = new Resource([]);
        $generator = new RoutesConsolidatorGenerator($resource, $context);

        $logger = Mockery::mock('stdClass');
        $logger->shouldReceive('info')
            ->with("# No routes to consolidate")
            ->once()
            ->andReturn(null);

        $generator->setLogger($logger);
        $generator->handle();

        $this->assertTrue(true);
    }
}
