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
@if ($resource->has('transformer.defaultIncludes'))
@foreach ($resource->transformer['defaultIncludes'] as $key => $value)
        '{{Str::kebab($key)}}',
@endforeach
@else
        //
@endif
    ];
    
    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected array $availableIncludes = [
@if ($resource->has('transformer.availableIncludes'))
@foreach ($resource->transformer['availableIncludes'] as $key => $value)
        '{{Str::kebab($key)}}',
@endforeach
@else
        //
@endif
    ];
    
    /**
     * A Fractal transformer.
     *
     * @param  {{$resource->name()->singular()->studly()}} ${{$resource->name()->singular()->lcfirst()}}
     * @return array
     */
    public function transform({{$resource->name()->singular()->studly()}} ${{$resource->name()->singular()->lcfirst()}})
    {
@if ($resource->has('transformer.transform'))
        return [
@foreach ($resource->transformer['transform'] as $key => $value)
            '{{$key}}' => ${{$resource->name()->singular()->lcfirst()}}->{{$value}},
@endforeach
        ];
@else
        return ${{$resource->name()->singular()->lcfirst()}}->toArray();
@endif
    }

@if ($resource->has('transformer.availableIncludes'))
@foreach ($resource->transformer['availableIncludes'] as $key => $value)
    /**
     * Include {{Str::slug($key, ' ')}}.
     *
@if ($key == Str::singular($key))
     * @return League\Fractal\ItemResource
@else
     * @return League\Fractal\CollectionResource
@endif
     */
    public function include{{Str::studly($key)}}({{$resource->name()->singular()->studly()}} ${{$resource->name()->singular()->lcfirst()}})
    {
        if (empty(${{$resource->name()->singular()->lcfirst()}}->{{Str::camel($key)}})) {
            return $this->null();
        }
@if ($key == Str::singular($key))
        return $this->item(${{$resource->name()->singular()->lcfirst()}}->{{Str::camel($key)}}, new {{$value}}());
@else
        return $this->collection(${{$resource->name()->singular()->lcfirst()}}->{{Str::camel($key)}}, new {{$value}}());
@endif
    }
@endforeach
@endif

@if ($resource->has('transformer.defaultIncludes'))
@foreach ($resource->transformer['defaultIncludes'] as $key => $value)
    /**
     * Include {{Str::slug($key, ' ')}}.
     *
@if ($key == Str::singular($key))
     * @return League\Fractal\ItemResource
@else
     * @return League\Fractal\CollectionResource
@endif
     */
    public function include{{Str::studly($key)}}({{$resource->name()->singular()->studly()}} ${{$resource->name()->singular()->lcfirst()}})
    {
        if (empty(${{$resource->name()->singular()->lcfirst()}}->{{Str::camel($key)}})) {
            return $this->null();
        }
@if ($key == Str::singular($key))
        return $this->item(${{$resource->name()->singular()->lcfirst()}}->{{Str::camel($key)}}, new {{$value}}());
@else
        return $this->collection(${{$resource->name()->singular()->lcfirst()}}->{{Str::camel($key)}}, new {{$value}}());
@endif
    }
@endforeach
@endif
}
