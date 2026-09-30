<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Codigo;
use Illuminate\Auth\Access\HandlesAuthorization;

class CodigoPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_codigo');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Codigo $codigo): bool
    {
        return $user->can('view_codigo');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_codigo');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Codigo $codigo): bool
    {
        return $user->can('update_codigo');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Codigo $codigo): bool
    {
        return $user->can('delete_codigo');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_codigo');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, Codigo $codigo): bool
    {
        return $user->can('force_delete_codigo');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_codigo');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Codigo $codigo): bool
    {
        return $user->can('restore_codigo');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_codigo');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, Codigo $codigo): bool
    {
        return $user->can('replicate_codigo');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_codigo');
    }
}
