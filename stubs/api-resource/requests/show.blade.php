@php
echo '<?php';
@endphp


namespace App\Http\Requests\Api\{{$resource->name()->singular()->studly()}};

use Firevel\Api\Http\Requests\Api\ApiRequest;
@if ($resource->has('requests.show.imports'))
@foreach ($resource->get('requests.show.imports') as $import)
use {{$import}};
@endforeach
@endif
@if ($resource->has('requests.show.use'))
@foreach ($resource->get('requests.show.use') as $name => $namespace)
use {{$namespace}};
@endforeach
@endif

class Show{{$resource->name()->singular()->studly()}} extends {{$resource->has('requests.show.extends') ? $resource->get('requests.show.extends') : 'ApiRequest' }}
{
@if ($resource->has('requests.show.use'))
@foreach ($resource->get('requests.show.use') as $name => $namespace)
    use {{$name}};
@endforeach

@endif
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
@if ($resource->has('requests.show.rules'))
@foreach ($resource->get('requests.show.rules') as $key => $value)
            '{{$key}}' => '{{$value}}',
@endforeach
@endif

        ];
    }
}
