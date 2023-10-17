{!! '<?php' !!}

namespace App\Models;

use Firevel\Filterable\Filterable;
use Firevel\Sortable\Sortable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
@if ($resource->has('relationships'))
@foreach ($resource->relationships()->unique() as $relationship)
use Illuminate\Database\Eloquent\Relations\{{Str::studly($relationship)}}
@endforeach
@endif

class {{$resource->name()->singular()->studly()}} extends Model
{
    use Sortable, HasFactory, Filterable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
@if ($resource->has('model.fillable'))
@foreach ($resource->model['fillable'] as $value)
        '{{$value}}',
@endforeach
@endif
    ];

    /**
     * Json objects
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

    /**
     * Fields allowed for sorting.
     *
     * @var array
     */
    protected $sortable = [
@if ($resource->has('model.sortable'))
@foreach ($resource->model['sortable'] as $value)
        '{{$value}}',
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
    public function {{$key}}(): {{Str::studly($value)}}
    {
        return $this->{{Str::camel($value)}}(\App\Models\{{Str::studly(Str::singular($key))}}::class);
    }
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
