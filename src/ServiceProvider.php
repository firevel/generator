<?php

namespace Firevel\Generator;

use Firevel\Generator\Console\Commands\Generate;
use Firevel\Generator\Console\Commands\MakeApiResource;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

/**
 * Class ServiceProvider
 * @package Firevel\Generator
 */
class ServiceProvider extends IlluminateServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Generate::class
            ]);
            $this->loadViewsFrom(__DIR__.'/../stubs', 'firevel-generator');
        }

        $this->publishes([
            __DIR__.'/../config/config.php' => config_path('generator.php'),
        ], 'config');
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'generator');
    }
}
