<?php

namespace Firevel\Generator\Tests\Generator\ApiResource;

use Orchestra\Testbench\Concerns\WithWorkbench;
use Firevel\Generator\Generators\ApiResource\ModelGenerator;
use Firevel\Generator\Resource;

class ModelGeneratorTest extends \Orchestra\Testbench\TestCase
{
    use WithWorkbench;

    /** @test */
    public function test_class_name()
    {
        $resource = new Resource([
            'name' => 'User',
        ]);
        $generator = new ModelGenerator($resource);

        $this->assertStringContainsString('class User', $generator->generateSource());
    }

    /** @test */
    public function test_fillable()
    {
        $resource = new Resource([
            'name' => 'User',
            'model' => [
                'fillable' => ['first_name']
            ]
        ]);
        $generator = new ModelGenerator($resource);

        // Define the regular expression pattern to find 'foo' between '$fillable =' and ']'
        $pattern = '/\$fillable\s*=\s*\[(.*?)first_name(.*?)]/s';

        // Assert that the pattern is found within the string
        $this->assertMatchesRegularExpression($pattern, $generator->generateSource());
    }


}
