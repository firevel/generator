@php
echo '<?php';
@endphp


namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\{{$resource->name()->singular()->studly()}}>
 */
class {{$resource->name()->singular()->studly()}}Factory extends Factory
{
@if ($resource->has('factories.definition'))
    /**
     * Define the models default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
@foreach ($resource->factories['definition'] as $attribute => $chain)
            '{{$attribute}}' => }@foreach ($chain as $method => $params){{$loop->first ? '': '->'}}{{$method}}({!! empty($params) ? '' : collect($params)
                ->transform(function($param) {
                    if (is_string($param) && $param[0] !== '$') {
                        return "'{$param}'";
                    }
                    return $param;
                })
                ->implode(', ')!!}),@endforeach

@endforeach
        ];
    }
@endif
}
