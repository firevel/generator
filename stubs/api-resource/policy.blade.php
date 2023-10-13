{!! '<?php' !!}

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
     * @param Authenticatable $user
     * @return mixed
     */
    public function viewAny(Authenticatable $user)
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param Authenticatable $user
     * @param {{$resource->name()->singular()->studly()}} ${{$resource->name()->singular()->lcfirst()}}
     * @return mixed
     */
    public function view(Authenticatable $user, {{$resource->name()->singular()->studly()}} ${{$resource->name()->singular()->lcfirst()}}
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param Authenticatable $user
     * @return mixed
     */
    public function create(Authenticatable $user)
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param Authenticatable $user
     * @param {{$resource->name()->singular()->studly()}} ${{$resource->name()->singular()->lcfirst()}}
     * @return mixed
     */
    public function update(Authenticatable $user, {{$resource->name()->singular()->studly()}} ${{$resource->name()->singular()->lcfirst()}}
    {
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param Authenticatable $user
     * @param {{$resource->name()->singular()->studly()}} ${{$resource->name()->singular()->lcfirst()}}
     * @return mixed
     */
    public function delete(Authenticatable $user, {{$resource->name()->singular()->studly()}} ${{$resource->name()->singular()->lcfirst()}}
    {
        return true;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param Authenticatable $user
     * @param {{$resource->name()->singular()->studly()}} ${{$resource->name()->singular()->lcfirst()}}
     * @return mixed
     */
    public function restore(Authenticatable $user, {{$resource->name()->singular()->studly()}} ${{$resource->name()->singular()->lcfirst()}}
    {
        return true;
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param Authenticatable $user
     * @param {{$resource->name()->singular()->studly()}} ${{$resource->name()->singular()->lcfirst()}}
     * @return mixed
     */
    public function forceDelete(Authenticatable $user, {{$resource->name()->singular()->studly()}} ${{$resource->name()->singular()->lcfirst()}}
    {
        return true;
    }
}
