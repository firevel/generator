@php
echo '<?php';
@endphp


namespace App\Http\Requests\Api\{{$resource->name()->singular()->studly()}};

use Firevel\Api\Http\Requests\Api\ApiRequest;
@if ($resource->has('requests.update.imports'))
@foreach ($resource->get('requests.update.imports') as $import)
use {{$import}};
@endforeach
@endif
@if ($resource->has('requests.update.use'))
@foreach ($resource->get('requests.update.use') as $name => $namespace)
use {{$namespace}};
@endforeach
@endif

class Update{{$resource->name()->singular()->studly()}} extends {{$resource->has('requests.update.extends') ? $resource->get('requests.update.extends') : 'ApiRequest' }}
{
@if ($resource->has('requests.update.use'))
@foreach ($resource->get('requests.update.use') as $name => $namespace)
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
@if ($resource->has('requests.update.rules'))
@foreach ($resource->get('requests.update.rules') as $key => $value)
            '{{$key}}' => '{{$value}}',
@endforeach
@endif
        ];
    }
}
