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

    /** @test */
    public function test_relationship_use()
    {
        $resource = new Resource([
            'name' => 'Comment',
            'model' => [
                'relationships' => [
                    'user' => 'belongsTo'
                ]
            ]
        ]);
        $generator = new ModelGenerator($resource);

        $this->assertStringContainsString('function user(): BelongsTo', $generator->generateSource());
        $this->assertStringContainsString('$this->belongsTo(\App\Models\User::class)', $generator->generateSource());
    }

    /** @test */
    public function test_relationship_strings()
    {
        $resource = new Resource([
            'name' => 'Comment',
            'model' => [
                'relationships' => [
                    'user' => [
                        'belongsTo' => ['User::class', 'foreign_key', 'owner_key']
                    ],
                    'role' => [
                        'belongsToMany' => ['Role::class']
                    ]

                ]
            ]
        ]);
        $generator = new ModelGenerator($resource);
        $source = $generator->generateSource();

        $this->assertStringContainsString('use Illuminate\Database\Eloquent\Relations\BelongsTo;', $source);
        $this->assertStringContainsString('use Illuminate\Database\Eloquent\Relations\BelongsToMany;', $source);
    }

    /** @test */
    public function test_relationship_multiparams()
    {
        $resource = new Resource([
            'name' => 'Comment',
            'model' => [
                'relationships' => [
                    'user' => [
                        'belongsTo' => ['User::class', 'foreign_key', 'owner_key']
                    ]
                ]
            ]
        ]);
        $generator = new ModelGenerator($resource);

        $this->assertStringContainsString('function user(): BelongsTo', $generator->generateSource());
        $this->assertStringContainsString('$this->belongsTo(\App\Models\User::class, \'foreign_key\', \'owner_key\')', $generator->generateSource());
    }

    /** @test */
    public function test_relationship_singleparam()
    {
        $resource = new Resource([
            'name' => 'User',
            'model' => [
                'relationships' => [
                    'role' => [
                        'belongsToMany' => ['Role::class']
                    ]
                ]
            ]
        ]);
        $generator = new ModelGenerator($resource);

        $this->assertStringContainsString('function role(): BelongsToMany', $generator->generateSource());
        $this->assertStringContainsString('$this->belongsToMany(\App\Models\Role::class)', $generator->generateSource());
    }

    /** @test */
    public function test_boolean_model_properties()
    {
        $resource = new Resource([
            'name' => 'User',
            'model' => [
                'properties' => [
                    'incrementing' => false
                ]
            ]
        ]);
        $generator = new ModelGenerator($resource);

        $this->assertStringContainsString('protected $incrementing = false;', $generator->generateSource());
    }

    /** @test */
    public function test_integer_model_properties()
    {
        $resource = new Resource([
            'name' => 'User',
            'model' => [
                'properties' => [
                    'userId' => 11
                ]
            ]
        ]);
        $generator = new ModelGenerator($resource);

        $this->assertStringContainsString('protected $userId = 11;', $generator->generateSource());
    }

    /** @test */
    public function test_string_model_properties()
    {
        $resource = new Resource([
            'name' => 'User',
            'model' => [
                'properties' => [
                    'primaryKey' => 'uuid'
                ]
            ]
        ]);
        $generator = new ModelGenerator($resource);

        $this->assertStringContainsString('protected $primaryKey = "uuid";', $generator->generateSource());
    }

}
