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
}
