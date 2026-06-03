<?php

namespace Firevel\Generator\Tests\Feature;

use Firevel\Generator\ServiceProvider;
use Illuminate\Support\Facades\Artisan;
use Orchestra\Testbench\TestCase;

/**
 * Smoke-test the built-in pipeline contracts via the full `firevel:generate`
 * command, since these are the user-facing error messages.
 */
class InputContractIntegrationTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [ServiceProvider::class];
    }

    /** @test */
    public function test_api_resource_pipeline_rejects_multi_resource_input()
    {
        $jsonFile = tempnam(sys_get_temp_dir(), 'contract_') . '.json';
        file_put_contents($jsonFile, json_encode([
            'resources' => [
                ['name' => 'Article'],
                ['name' => 'Comment'],
            ],
        ]));

        try {
            $exit = Artisan::call('firevel:generate', [
                'pipeline' => 'api-resource',
                '--json' => $jsonFile,
            ]);
            $output = Artisan::output();

            $this->assertSame(1, $exit);
            $this->assertStringContainsString("Pipeline 'api-resource' input does not match", $output);
            $this->assertStringContainsString("does not accept a `resources` array", $output);
            $this->assertStringContainsString('generic-app', $output);
        } finally {
            unlink($jsonFile);
        }
    }

    /** @test */
    public function test_generic_app_pipeline_rejects_missing_resources()
    {
        $jsonFile = tempnam(sys_get_temp_dir(), 'contract_') . '.json';
        file_put_contents($jsonFile, json_encode(['name' => 'NotMultiResource']));

        try {
            $exit = Artisan::call('firevel:generate', [
                'pipeline' => 'generic-app',
                '--json' => $jsonFile,
            ]);
            $output = Artisan::output();

            $this->assertSame(1, $exit);
            $this->assertStringContainsString("Pipeline 'generic-app' input does not match", $output);
            $this->assertStringContainsString("`resources`", $output);
        } finally {
            unlink($jsonFile);
        }
    }

    /** @test */
    public function test_appengine_app_requires_both_service_and_resources()
    {
        $jsonFile = tempnam(sys_get_temp_dir(), 'contract_') . '.json';
        file_put_contents($jsonFile, json_encode([]));

        try {
            $exit = Artisan::call('firevel:generate', [
                'pipeline' => 'appengine-app',
                '--json' => $jsonFile,
            ]);
            $output = Artisan::output();

            $this->assertSame(1, $exit);
            $this->assertStringContainsString('`service`', $output);
            $this->assertStringContainsString('`resources`', $output);
        } finally {
            unlink($jsonFile);
        }
    }

    /** @test */
    public function test_generic_app_accepts_and_ignores_a_service_block()
    {
        // generic-app does not consume `service`, but passing one should be
        // tolerated (ignored) rather than rejected.
        $manager = app(\Firevel\Generator\FirevelGeneratorManager::class);
        $schema = $manager->getInputSchema('generic-app');
        $messages = $manager->getInputErrorMessages('generic-app');

        $errors = \Firevel\Generator\Validation\InputSchemaValidator::validate(
            [
                'service' => ['name' => 'api', 'runtime' => 'php83'],
                'resources' => [['name' => 'Article']],
            ],
            $schema,
            $messages,
            'generic-app'
        );

        $this->assertSame([], $errors);
    }
}
