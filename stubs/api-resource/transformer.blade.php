{!! '<?php' !!}

namespace App\Transformers;

use App\Models\{{$resource->name()->studly()}};
use League\Fractal\TransformerAbstract;

class {{$resource->name()->singular()->studly()}}Transformer extends TransformerAbstract
{
    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected array $defaultIncludes = [
        //
    ];
    
    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected array $availableIncludes = [
        //
    ];
    
    /**
     * A Fractal transformer.
     *
     * @param  {{$resource->name()->singular()->studly()}} ${{$resource->name()->singular()->lcfirst()}}
     * @return array
     */
    public function transform({{$resource->name()->singular()->studly()}} ${{$resource->name()->singular()->lcfirst()}})
    {
@if ($resource->has('transformable'))
        return [
@foreach ($resource->transformable as $key => $value)
            '{{$key}}' => ${{$resource->name()->singular()->lcfirst()}}->{{$value}},
@endforeach
        ];
@else
        return ${{$resource->name()->singular()->lcfirst()}}->toArray();
@endif
    }
}
