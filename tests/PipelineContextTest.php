<?php

namespace Firevel\Generator\Tests;

use Firevel\Generator\PipelineContext;
use PHPUnit\Framework\TestCase;

class PipelineContextTest extends TestCase
{
    /** @test */
    public function test_creates_standalone_context_by_default()
    {
        $context = new PipelineContext();

        $this->assertFalse($context->isMetaPipeline());
    }

    /** @test */
    public function test_creates_meta_pipeline_context()
    {
        $context = new PipelineContext(true);

        $this->assertTrue($context->isMetaPipeline());
    }

    /** @test */
    public function test_set_and_get_value()
    {
        $context = new PipelineContext();
        $context->set('key', 'value');

        $this->assertEquals('value', $context->get('key'));
    }

    /** @test */
    public function test_get_returns_default_for_missing_key()
    {
        $context = new PipelineContext();

        $this->assertNull($context->get('missing'));
        $this->assertEquals('default', $context->get('missing', 'default'));
    }

    /** @test */
    public function test_has_checks_key_existence()
    {
        $context = new PipelineContext();
        $context->set('exists', 'value');

        $this->assertTrue($context->has('exists'));
        $this->assertFalse($context->has('missing'));
    }

    /** @test */
    public function test_push_creates_array_and_appends_values()
    {
        $context = new PipelineContext();

        $context->push('items', 'first');
        $context->push('items', 'second');
        $context->push('items', 'third');

        $this->assertEquals(['first', 'second', 'third'], $context->get('items'));
    }

    /** @test */
    public function test_all_returns_all_data()
    {
        $context = new PipelineContext();
        $context->set('key1', 'value1');
        $context->set('key2', 'value2');

        $expected = [
            'key1' => 'value1',
            'key2' => 'value2',
        ];

        $this->assertEquals($expected, $context->all());
    }

    /** @test */
    public function test_push_maintains_separate_arrays()
    {
        $context = new PipelineContext();

        $context->push('routes', 'route1');
        $context->push('seeders', 'seeder1');
        $context->push('routes', 'route2');

        $this->assertEquals(['route1', 'route2'], $context->get('routes'));
        $this->assertEquals(['seeder1'], $context->get('seeders'));
    }
}
