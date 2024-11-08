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
}
