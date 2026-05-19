<?php

namespace Firevel\Generator\Tests;

use Firevel\Generator\Resource;
use PHPUnit\Framework\TestCase;

class ResourceTest extends TestCase
{
    /** @test */
    public function test_get_returns_null_when_key_is_missing(): void
    {
        $resource = new Resource(['name' => 'Article']);

        // The bug: PHP 8 turns "Undefined array key" into an exception, which
        // means callers writing `$resource->get('foo') ?? $fallback` never
        // reach the fallback. Resource::get must hand back null on miss.
        $this->assertNull($resource->get('missing'));
    }

    /** @test */
    public function test_get_accepts_a_default(): void
    {
        $resource = new Resource(['name' => 'Article']);

        $this->assertSame('fallback', $resource->get('missing', 'fallback'));
        $this->assertSame('Article', $resource->get('name', 'fallback'));
    }

    /** @test */
    public function test_get_supports_dot_notation_with_default(): void
    {
        $resource = new Resource(['model' => ['fillable' => ['title']]]);

        $this->assertSame(['title'], $resource->get('model.fillable'));
        $this->assertNull($resource->get('model.missing'));
        $this->assertSame('def', $resource->get('model.missing', 'def'));
    }

    /** @test */
    public function test_magic_get_returns_null_on_missing_key(): void
    {
        $resource = new Resource(['name' => 'Article']);

        // Property access used to fatal-error on missing keys; should be null now.
        $this->assertNull($resource->missing);
    }

    /** @test */
    public function test_has_is_consistent_with_get(): void
    {
        $resource = new Resource(['name' => 'Article', 'empty' => '']);

        $this->assertTrue($resource->has('name'));
        $this->assertFalse($resource->has('missing'));
        // has() uses !empty(), so an empty string reads as "absent".
        $this->assertFalse($resource->has('empty'));
    }
}
