@php
echo '<?php';
@endphp


use Illuminate\Support\Facades\Route;
@foreach ($routes as $route)
use App\Http\Controllers\Api\{{$route['resourceName']}}Controller;
@endforeach

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

@foreach ($routes as $route)
{{$route['code']}}
@endforeach
