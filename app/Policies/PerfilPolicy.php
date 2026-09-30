<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Perfil;
use Illuminate\Auth\Access\HandlesAuthorization;

class PerfilPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_perfil');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Perfil $perfil): bool
    {
        return $user->can('view_perfil');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_perfil');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Perfil $perfil): bool
    {
        return $user->can('update_perfil');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Perfil $perfil): bool
    {
        return $user->can('delete_perfil');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_perfil');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, Perfil $perfil): bool
    {
        return $user->can('force_delete_perfil');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_perfil');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Perfil $perfil): bool
    {
        return $user->can('restore_perfil');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_perfil');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, Perfil $perfil): bool
    {
        return $user->can('replicate_perfil');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_perfil');
    }
}
