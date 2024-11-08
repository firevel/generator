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

        $this->assertStringContainsString('extends ApiRequest', $generator->generateSource('index', $resource));
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
//


}
