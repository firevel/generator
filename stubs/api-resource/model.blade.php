@php
echo '<?php';
@endphp


namespace App\Models;

use Firevel\Filterable\Filterable;
use Firevel\Sortable\Sortable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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

class {{$resource->name()->singular()->studly()}} extends {{ $resource->has('model.authenticatable') ? 'Authenticatable' : 'Model'  }}
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
@if (is_string($value))
    protected {{'$'}}{{$name}} = "{{$value}}";

@endif
@if (is_bool($value))
    protected {{'$'}}{{$name}} = {{json_encode($value)}};

@endif
@if (is_int($value))
    protected {{'$'}}{{$name}} = {{$value}};

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
    public function {{$key}}(): {{Str::studly($value)}}
    {
        return $this->{{Str::camel($value)}}(\App\Models\{{Str::studly(Str::singular($key))}}::class);
    }
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
