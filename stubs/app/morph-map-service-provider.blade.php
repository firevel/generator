@php
echo '<?php';
@endphp


namespace App\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class MorphMapServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::morphMap([
@foreach ($morphMap as $alias => $class)
            '{{$alias}}' => \{{ ltrim($class, '\\') }}::class,
@endforeach
        ]);
    }
}
