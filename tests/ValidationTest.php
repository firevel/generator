<?php

namespace Firevel\Generator\Tests;

use Firevel\Generator\FirevelGeneratorManager;
use Firevel\Generator\Generators\BaseGenerator;
use Illuminate\Support\Facades\Config;

class ValidationTest extends \Orchestra\Testbench\TestCase
{
    protected FirevelGeneratorManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = new FirevelGeneratorManager();
        Config::set('generator.pipelines', []);
    }

    public function test_valid_class_step_produces_no_errors()
    {
        Config::set('generator.pipelines', [
            'good' => [
                'step-a' => ValidationTestGenerator::class,
            ],
        ]);

        $this->assertSame([], $this->manager->validate());
    }

    public function test_missing_class_is_flagged()
    {
        Config::set('generator.pipelines', [
            'bad' => [
                'oops' => 'Firevel\\NoSuchClass',
            ],
        ]);

        $errors = $this->manager->validate();
        $this->assertCount(1, $errors);
        $this->assertStringContainsString("pipeline 'bad' step 'oops'", $errors[0]);
        $this->assertStringContainsString("does not exist", $errors[0]);
    }

    public function test_non_basegenerator_class_is_flagged()
    {
        Config::set('generator.pipelines', [
            'bad' => [
                'wrong-base' => NotABaseGenerator::class,
            ],
        ]);

        $errors = $this->manager->validate();
        $this->assertCount(1, $errors);
        $this->assertStringContainsString("must extend", $errors[0]);
    }

    public function test_scoped_step_with_missing_target_is_flagged()
    {
        Config::set('generator.pipelines', [
            'parent' => [
                ['scope' => 'resources.*', 'pipeline' => 'never-registered'],
            ],
        ]);

        $errors = $this->manager->validate();
        $this->assertCount(1, $errors);
        $this->assertStringContainsString("references unknown pipeline 'never-registered'", $errors[0]);
    }

    public function test_scoped_step_with_valid_target_passes()
    {
        Config::set('generator.pipelines', [
            'child' => [
                'step' => ValidationTestGenerator::class,
            ],
            'parent' => [
                ['scope' => 'resources.*', 'pipeline' => 'child'],
            ],
        ]);

        $this->assertSame([], $this->manager->validate());
    }

    public function test_unrecognized_step_shape_is_flagged()
    {
        Config::set('generator.pipelines', [
            'weird' => [
                'wat' => 12345,
            ],
        ]);

        $errors = $this->manager->validate();
        $this->assertCount(1, $errors);
        $this->assertStringContainsString("unrecognized step shape", $errors[0]);
    }

    public function test_circular_scope_reference_is_flagged()
    {
        Config::set('generator.pipelines', [
            'a' => [
                ['scope' => 'x', 'pipeline' => 'b'],
            ],
            'b' => [
                ['scope' => 'x', 'pipeline' => 'a'],
            ],
        ]);

        $errors = $this->manager->validate();
        $cycleErrors = array_filter($errors, fn($e) => str_contains($e, 'circular'));
        $this->assertNotEmpty($cycleErrors);

        $errorText = implode(' ', $cycleErrors);
        $this->assertStringContainsString('a', $errorText);
        $this->assertStringContainsString('b', $errorText);
    }

    public function test_self_referential_scope_is_flagged_as_cycle()
    {
        Config::set('generator.pipelines', [
            'self' => [
                ['scope' => 'x', 'pipeline' => 'self'],
            ],
        ]);

        $errors = $this->manager->validate();
        $cycleErrors = array_filter($errors, fn($e) => str_contains($e, 'circular'));
        $this->assertNotEmpty($cycleErrors);
    }

    public function test_validate_works_on_hybrid_shape()
    {
        Config::set('generator.pipelines', [
            'hybrid' => [
                'description' => 'A described pipeline.',
                'steps' => [
                    'step' => 'Firevel\\NoSuchClass',
                ],
            ],
        ]);

        $errors = $this->manager->validate();
        $this->assertCount(1, $errors);
        $this->assertStringContainsString("does not exist", $errors[0]);
    }
}

class ValidationTestGenerator extends BaseGenerator
{
    public function handle()
    {
    }
}

class NotABaseGenerator
{
}
