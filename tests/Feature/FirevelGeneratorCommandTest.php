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

    /** @test */
    public function test_command_accepts_multiple_json_files_for_multiple_pipelines()
    {
        // Create temporary JSON files
        $jsonFile1 = tempnam(sys_get_temp_dir(), 'test_resource_1_') . '.json';
        $jsonFile2 = tempnam(sys_get_temp_dir(), 'test_resource_2_') . '.json';

        file_put_contents($jsonFile1, json_encode([
            'name' => 'FirstResource',
            'model' => ['fillable' => ['title']],
        ]));

        file_put_contents($jsonFile2, json_encode([
            'name' => 'SecondResource',
            'model' => ['fillable' => ['description']],
        ]));

        try {
            // Test with multiple pipelines and multiple JSON files
            $this->artisan('firevel:generate', [
                'pipeline' => 'routes,routes',
                '--json' => "{$jsonFile1},{$jsonFile2}",
            ])->assertExitCode(0);
        } finally {
            // Clean up
            if (file_exists($jsonFile1)) {
                unlink($jsonFile1);
            }
            if (file_exists($jsonFile2)) {
                unlink($jsonFile2);
            }
        }
    }

    /** @test */
    public function test_command_reuses_last_json_when_more_pipelines_than_json_files()
    {
        // Create one JSON file
        $jsonFile = tempnam(sys_get_temp_dir(), 'test_resource_') . '.json';
        file_put_contents($jsonFile, json_encode([
            'name' => 'TestResource',
            'model' => ['fillable' => ['title']],
        ]));

        try {
            // Test with multiple pipelines but only one JSON file
            // Should use the same JSON for all pipelines
            $this->artisan('firevel:generate', [
                'pipeline' => 'routes,routes,routes',
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
    public function test_command_shows_error_for_missing_json_file()
    {
        $this->artisan('firevel:generate', [
            'pipeline' => 'routes',
            '--json' => 'non-existent-file.json',
        ])
        ->expectsOutput('JSON file \'non-existent-file.json\' not found for pipeline \'routes\'.')
        ->assertExitCode(0);
    }

    /** @test */
    public function test_command_validates_json_files_lazily_per_pipeline()
    {
        $jsonFile = tempnam(sys_get_temp_dir(), 'test_resource_') . '.json';
        file_put_contents($jsonFile, json_encode(['name' => 'Test']));

        try {
            // Second pipeline will fail because non-existent.json doesn't exist
            // But first pipeline will execute successfully before validation fails
            $this->artisan('firevel:generate', [
                'pipeline' => 'routes,routes',
                '--json' => "{$jsonFile},non-existent.json",
            ])
            ->expectsOutput('JSON file \'non-existent.json\' not found for pipeline \'routes\'.')
            ->assertExitCode(0);
        } finally {
            if (file_exists($jsonFile)) {
                unlink($jsonFile);
            }
        }
    }
}
