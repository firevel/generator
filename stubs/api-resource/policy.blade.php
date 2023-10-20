@php
echo '<?php';
@endphp


namespace App\Policies;

use App\Models\{{$resource->name()->singular()->studly()}};
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Auth\Access\HandlesAuthorization;

class {{$resource->name()->singular()->studly()}}Policy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param Authenticatable $authenticatable
     * @return mixed
     */
    public function viewAny(Authenticatable $authenticatable)
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param Authenticatable $authenticatable
     * @param {{$resource->name()->singular()->studly()}} ${{$resource->name()->singular()->lcfirst()}}
     * @return mixed
     */
    public function view(Authenticatable $authenticatable, {{$resource->name()->singular()->studly()}} ${{$resource->name()->singular()->lcfirst()}})
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param Authenticatable $authenticatable
     * @return mixed
     */
    public function create(Authenticatable $authenticatable)
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param Authenticatable $authenticatable
     * @param {{$resource->name()->singular()->studly()}} ${{$resource->name()->singular()->lcfirst()}}
     * @return mixed
     */
    public function update(Authenticatable $authenticatable, {{$resource->name()->singular()->studly()}} ${{$resource->name()->singular()->lcfirst()}})
    {
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param Authenticatable $authenticatable
     * @param {{$resource->name()->singular()->studly()}} ${{$resource->name()->singular()->lcfirst()}}
     * @return mixed
     */
    public function delete(Authenticatable $authenticatable, {{$resource->name()->singular()->studly()}} ${{$resource->name()->singular()->lcfirst()}})
    {
        return true;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param Authenticatable $authenticatable
     * @param {{$resource->name()->singular()->studly()}} ${{$resource->name()->singular()->lcfirst()}}
     * @return mixed
     */
    public function restore(Authenticatable $authenticatable, {{$resource->name()->singular()->studly()}} ${{$resource->name()->singular()->lcfirst()}})
    {
        return true;
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param Authenticatable $authenticatable
     * @param {{$resource->name()->singular()->studly()}} ${{$resource->name()->singular()->lcfirst()}}
     * @return mixed
     */
    public function forceDelete(Authenticatable $authenticatable, {{$resource->name()->singular()->studly()}} ${{$resource->name()->singular()->lcfirst()}})
    {
        return true;
    }
}
