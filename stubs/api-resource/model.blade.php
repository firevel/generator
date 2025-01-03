@php
echo '<?php';
@endphp


namespace App\Models;

use Firevel\Filterable\Filterable;
use Firevel\Sortable\Sortable;
@if ($resource->has('model.observers'))
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
@endif
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
@if ($resource->has('model.relationships'))
@foreach (collect($resource->model['relationships'])->transform(fn($relationship) => is_string($relationship) ? $relationship : array_key_first($relationship))->unique()->values() as $relationship)
use Illuminate\Database\Eloquent\Relations\{{Str::studly($relationship)}};
@endforeach
@endif
@if ($resource->has('model.use'))
@foreach ($resource->model['use'] as $name => $namespace)
use {{$namespace}};
@endforeach
@endif

@if ($resource->has('model.observers'))
@foreach ($resource->model['observers'] as $observer)
@if (str_contains($observer, '\\'))
#[ObservedBy([{{$observer}}::class])]
@else
#[ObservedBy([\App\Observers\{{$observer}}::class])]
@endif
@endforeach
@endif
class {{$resource->name()->singular()->studly()}} extends {{ $resource->has('model.authenticatable') ? 'Authenticatable' : ($resource->has('model.extends') ? $resource->model['extends'] : 'Model')  }}
{
    use HasFactory,
@if ($resource->has('model.use'))
@foreach ($resource->model['use'] as $name => $namespace)
    {{$name}},
@endforeach
@endif
    Sortable,
    Filterable;

@if ($resource->has('model.properties'))
@foreach ($resource->model['properties'] as $name => $value)
@php
    $propertyName = is_array($value) ? $value['name'] : $name;
    $propertyValue = is_array($value) ? $value['value'] : $value;
    $visibility = is_array($value) && isset($value['visibility']) ? $value['visibility'] : 'protected';
@endphp
@if (is_string($propertyValue))
    {{$visibility}} {{'$'}}{{$propertyName}} = "{{$propertyValue}}";
@elseif (is_bool($propertyValue))
    {{$visibility}} {{'$'}}{{$propertyName}} = {{json_encode($propertyValue)}};
@elseif (is_int($propertyValue))
    {{$visibility}} {{'$'}}{{$propertyName}} = {{$propertyValue}};
@endif
@endforeach
@endif

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
@if ($resource->has('model.fillable'))
@foreach ($resource->model['fillable'] as $value)
        '{{$value}}',
@endforeach
@endif
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
@if ($resource->has('model.casts'))
@foreach ($resource->model['casts'] as $key => $value)
        '{{$key}}' => '{{$value}}',
@endforeach
@endif
    ];
@if ($resource->has('model.touches'))

    /**
     * All of the relationships to be touched.
     *
     * @var array
     */
    protected $touches = [
@foreach ($resource->model['touches'] as $value)
        '{{$value}}',
@endforeach
    ];
@endif

    /**
     * Fields allowed for sorting.
     *
     * @var array<int, string>
     */
    protected $sortable = [
@if ($resource->has('model.sortable'))
@foreach ($resource->model['sortable'] as $value)
        '{{$value}}',
@endforeach
@endif
    ];

    /**
     * The models default values for attributes.
     * 
     * @var array<int, string>
     */
    protected $attributes = [
@if ($resource->has('model.attributes'))
@foreach ($resource->model['attributes'] as $key => $value)
        '{{$key}}' => '{{$value}}',
@endforeach
@endif
    ];

    /**
     * Fields with filtering.
     * 
     * @var array
     */
    protected $filterable = [
@if ($resource->has('model.filterable'))
@foreach ($resource->model['filterable'] as $key => $value)
        '{{$key}}' => '{{$value}}',
@endforeach
@endif
    ];

@if ($resource->has('model.relationships'))
@foreach ($resource->model['relationships'] as $key => $value)
@if (is_string($value))
@if ($value == 'morphTo')
    public function {{$key}}(): {{Str::studly($value)}}
    {
        return $this->{{Str::camel($value)}}();
    }
@else
    public function {{$key}}(): {{Str::studly($value)}}
    {
        return $this->{{Str::camel($value)}}(\App\Models\{{Str::studly(Str::singular($key))}}::class);
    }
@endif
@else
@php
   $relationship = array_key_first($value);
@endphp
    public function {{$key}}(): {{Str::studly($relationship)}}
    {
        return $this->@foreach($value as $key => $params){{Str::camel($key)}}({!!collect($params)->transform(function($param, $key) {return (str_ends_with($param, '::class')) ? "\\App\\Models\\$param": "'{$param}'";})->implode(', ')!!});@endforeach

    }
@endif
@endforeach

@endif

    /**
     * Scope a query to rows visible by user.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Authenticatable $user
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVisibleBy($query, Authenticatable $user)
    {
        return $query;
    }
}
