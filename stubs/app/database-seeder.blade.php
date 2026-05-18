@php
echo '<?php';
@endphp


namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
@foreach ($classes as $class)
        $this->call({{ $class }}::class);
@endforeach
    }
}
