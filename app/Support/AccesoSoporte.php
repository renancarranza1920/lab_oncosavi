<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class AccesoSoporte
{
    private const ROLES_RESERVADOS = ['super_admin', 'superadmin', 'super admin', 'super-admin'];

    public static function rolReservado(?string $nombre): bool
    {
        return in_array(strtolower(trim($nombre ?? '')), self::ROLES_RESERVADOS, true);
    }

    public static function esCuentaReservada(?User $user): bool
    {
        return $user && $user->roles->contains(fn ($rol) => self::rolReservado($rol->name));
    }

    public static function rolesReservados(Builder $query): Builder
    {
        $columna = $query->getModel()->qualifyColumn('name');

        return $query->whereIn(\Illuminate\Support\Facades\DB::raw("LOWER(TRIM({$columna}))"), self::ROLES_RESERVADOS);
    }

    public static function cuentasReservadas(Builder $query): Builder
    {
        return $query->whereHas('roles', fn (Builder $roles) => self::rolesReservados($roles));
    }

    public static function usuariosVisibles(Builder $query, ?User $actor): Builder
    {
        return self::autorizado($actor) ? $query : $query->whereDoesntHave('roles', fn (Builder $roles) => self::rolesReservados($roles));
    }

    public static function rolesVisibles(Builder $query, ?User $actor): Builder
    {
        return self::autorizado($actor) ? $query : $query->whereNotIn(
            $query->getModel()->getQualifiedKeyName(),
            self::rolesReservados(\Spatie\Permission\Models\Role::query())->select('id'),
        );
    }

    public static function autorizado(?User $user): bool
    {
        return $user && $user->hasRole('admin') && $user->can('view_any_activity::log')
            && (self::esCuentaReservada($user) || $user->getDirectPermissions()->contains('name', 'ver_bitacora_soporte'));
    }
}
