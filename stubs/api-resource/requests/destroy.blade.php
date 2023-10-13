{!! '<?php' !!}

namespace App\Http\Requests\Api\{{$resource->name()->singular()->studly()}};

use Firevel\ApiResourceGenerator\Http\Requests\ApiRequest;

class Destroy{{$resource->name()->singular()->studly()}} extends ApiRequest
{
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
@if ($resource->has('requests.destroy.rules'))
@foreach ($resource->get('requests.store.rules') as $key => $value)
            '{{$key}}' => '{{$value}}',
@endforeach
@endif
        ];
    }
}
