@php
echo '<?php';
@endphp


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('{{$pivot['table']}}', function (Blueprint $table) {
@foreach ($pivot['fields'] as $field)
            {{ '$table' }}->unsignedBigInteger('{{$field['name']}}');
@endforeach
            {{ '$table' }}->primary([{!! collect($pivot['fields'])->map(fn($f) => "'{$f['name']}'")->implode(', ') !!}]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('{{$pivot['table']}}');
    }
};
