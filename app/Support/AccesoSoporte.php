<?php

namespace App\Support;

use App\Models\User;

class AccesoSoporte
{
    public static function autorizado(?User $user): bool
    {
        return $user && $user->can('view_any_activity::log')
            && ($user->hasRole('super_admin') || $user->getDirectPermissions()->contains('name', 'ver_bitacora_soporte'));
    }
}
