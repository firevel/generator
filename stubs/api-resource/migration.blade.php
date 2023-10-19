{!! '<?php' !!}
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
@if ($resource->has('migration.create'))
        Schema::create('{{$resource->name()->plural()->snake()}}', function (Blueprint $table) {
@foreach ($resource->migration['create'] as $chain)
            {{ '$table' }}@foreach ($chain as $method => $params)->{{$method}}({!! empty($params) ? '' : collect($params)
                ->transform(function($param) {
                    if (is_string($param)) {
                        return "'{$param}'";
                    }
                    return $param;
                })
                ->implode(', ')!!}){{$loop->last ? ';': ''}}@endforeach
@endforeach
        });
@endif

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('{{$resource->name()->plural()->snake()}}');
    }
};
