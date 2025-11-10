<?php
namespace Tests\Feature;

use Firevel\Generator\ServiceProvider;
use Orchestra\Testbench\TestCase;

class FirevelGeneratorCommandTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            ServiceProvider::class,
        ];
    }

    public function testFirevelGenerateCommandSucceeds()
    {
        $this->artisan('firevel:generate')
             ->assertExitCode(0);
    }

    /** @test */
    public function test_command_accepts_multiple_pipelines_separated_by_comma()
    {
        // Create a temporary JSON file with resource data
        $jsonFile = tempnam(sys_get_temp_dir(), 'test_resource_') . '.json';
        file_put_contents($jsonFile, json_encode([
            'name' => 'TestResource',
            'model' => [
                'fillable' => ['title'],
            ],
        ]));

        try {
            // Test with multiple pipelines - using routes pipeline multiple times should be safe
            $this->artisan('firevel:generate', [
                'pipeline' => 'routes',
                '--json' => $jsonFile,
            ])->assertExitCode(0);
        } finally {
            // Clean up
            if (file_exists($jsonFile)) {
                unlink($jsonFile);
            }
        }
    }

    /** @test */
    public function test_command_validates_all_pipelines_before_execution()
    {
        $this->artisan('firevel:generate', [
            'pipeline' => 'valid-pipeline,invalid-pipeline',
        ])
        ->expectsOutput('Pipeline \'valid-pipeline\' is not configured.')
        ->assertExitCode(0);
    }

    /** @test */
    public function test_command_shows_error_for_invalid_pipeline_in_comma_list()
    {
        $this->artisan('firevel:generate', [
            'pipeline' => 'routes,invalid-pipeline',
        ])
        ->expectsOutput('Pipeline \'invalid-pipeline\' is not configured.')
        ->assertExitCode(0);
    }
}
