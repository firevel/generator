{!! "<\?php" !!}

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\{{$resource->name()->singular()->studly()}}\Destroy{{$resource->name()->singular()->studly()}};
use App\Http\Requests\Api\{{$resource->name()->singular()->studly()}}\Index{{$resource->name()->plural()->studly()}};
use App\Http\Requests\Api\{{$resource->name()->singular()->studly()}}\Store{{$resource->name()->singular()->studly()}};
use App\Http\Requests\Api\{{$resource->name()->singular()->studly()}}\Show{{$resource->name()->singular()->studly()}};
use App\Http\Requests\Api\{{$resource->name()->singular()->studly()}}\Update{{$resource->name()->singular()->studly()}};
use App\Models\{{$resource->name()->singular()->studly()}};
use App\Transformers\{{$resource->name()->singular()->studly()}}Transformer;
use Illuminate\Support\Facades\Response;

class {{$resource->name()->plural()->studly()}}Controller extends Controller
{
    use RespondsWithJson;

    /**
     * @var {{$resource->name()->singular()->studly()}}Transformer
     */
    protected $transformer;

    /**
     * @param {{$resource->name()->singular()->studly()}}Transformer $transformer
     */
    public function __construct({{$resource->name()->singular()->studly()}}Transformer $transformer)
    {
        $this->middleware('auth:api');

        $this->transformer = $transformer;

        $this->authorizeResource({{$resource->name()->singular()->studly()}}::class, '{{$resource->name()->singular()->camel()}}');
    }

    /**
     * Display a listing of the resource.
     *
     * @param Index{{$resource->name()->plural()->studly()}} $request
     * @return Response
     */
    public function index(Index{{$resource->name()->plural()->studly()}} $request)
    {
        ${{$resource->name()->plural()->lcfirst()}} = {{$resource->name()->singular()->studly()}}::filter($request->filter)
            ->visibleBy($request->user())
            ->with($request->getIncludes())
            ->sort($request->getSort())
            ->paginate(
                $request->getPageSize()
            );

        return fractal(${{$resource->name()->plural()->lcfirst()}}, $this->transformer)
            ->parseIncludes($request->get('include'))
            ->respond();
    }

    /**
     * Store a newly created resource.
     *
     * @param Store{{$resource->name()->singular()->studly()}} $request
     * @return Response
     */
    public function store(Store{{$resource->name()->singular()->studly()}} $request)
    {
        ${{$resource->name()->singular()->lcfirst()}} = {{$resource->name()->singular()->studly()}}::create($request->validated());

        return fractal(${{$resource->name()->singular()->lcfirst()}}, $this->transformer)
            ->parseIncludes($request->get('include'))
            ->respond(201);
    }

    /**
     * Display the specified resource.
     *
     * @param Show{{$resource->name()->singular()->studly()}} $request
     * @param {{$resource->name()->singular()->studly()}} ${{$resource->name()->singular()->lcfirst()}}
     * @return Response
     */
    public function show(Show{{$resource->name()->singular()->studly()}} $request, {{$resource->name()->singular()->studly()}} ${{$resource->name()->singular()->lcfirst()}})
    {
        return fractal(${{$resource->name()->singular()->lcfirst()}}, $this->transformer)
            ->parseIncludes($request->get('include'))
            ->respond();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Update{{$resource->name()->singular()->studly()}} $request
     * @param {{$resource->name()->singular()->studly()}} ${{$resource->name()->singular()->lcfirst()}}
     * @return Response
     */
    public function update(Update{{$resource->name()->singular()->studly()}} $request, {{$resource->name()->singular()->studly()}} ${{$resource->name()->singular()->lcfirst()}})
    {
        ${{$resource->name()->singular()->lcfirst()}}->fill($request->validated())
            ->save();

        return fractal(${{$resource->name()->singular()->lcfirst()}}, $this->transformer)
            ->parseIncludes($request->get('include'))
            ->respond();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Destroy{{$resource->name()->singular()->studly()}} $request
     * @param {{$resource->name()->singular()->studly()}} ${{$resource->name()->singular()->lcfirst()}}
     * @return Response
     */
    public function destroy(Destroy{{$resource->name()->singular()->studly()}} $request, {{$resource->name()->singular()->studly()}} ${{$resource->name()->singular()->lcfirst()}})
    {
        ${{$resource->name()->singular()->lcfirst()}}->delete();

        return Response::json(null, 204);
    }
}
