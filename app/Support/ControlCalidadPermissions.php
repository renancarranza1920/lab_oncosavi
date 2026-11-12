<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class ControlCalidadPermissions
{
    public const VIEW = 'ver_control_calidad_interno';

    public const MAINTAIN = 'mantener_control_calidad_interno';

    public const REGISTER = 'registrar_control_calidad_interno';

    public const EDIT = 'editar_control_calidad_interno';

    public const DELETE = 'eliminar_control_calidad_interno';

    public const CHARTS = 'ver_graficos_control_calidad_interno';

    public const HISTORY = 'ver_historial_control_calidad_interno';

    public const EXPORT = 'exportar_control_calidad_interno';

    public static function options(): array
    {
        return [
            self::VIEW => 'Acceder al módulo y ver el resumen',
            self::MAINTAIN => 'Administrar áreas, equipos, productos, lotes, pruebas y niveles',
            self::REGISTER => 'Registrar controles',
            self::EDIT => 'Editar registros',
            self::DELETE => 'Eliminar registros',
            self::CHARTS => 'Consultar gráficos',
            self::HISTORY => 'Consultar el historial',
            self::EXPORT => 'Exportar PDF de datos y gráficos',
        ];
    }

    public static function all(): array
    {
        return array_keys(self::options());
    }

    public static function groups(): array
    {
        $options = self::options();

        return [
            'Acceso y consultas' => array_intersect_key($options, array_flip([self::VIEW, self::CHARTS, self::HISTORY])),
            'Mantenimiento' => array_intersect_key($options, array_flip([self::MAINTAIN])),
            'Registros' => array_intersect_key($options, array_flip([self::REGISTER, self::EDIT, self::DELETE])),
            'Exportación' => array_intersect_key($options, array_flip([self::EXPORT])),
        ];
    }

    /** Add the module permissions without replacing any existing role grants. */
    public static function register(): void
    {
        DB::transaction(function (): void {
            $permissions = collect(self::all())->map(fn (string $name): Permission => Permission::findOrCreate($name, 'web'));
            $panel = Permission::findOrCreate('access_admin_panel', 'web');
            $permissions->push($panel);
            $roles = array_unique(['admin', 'laboratorista', 'superadmin', mb_strtolower((string) config('filament-shield.super_admin.name', 'super_admin'))]);
            Role::query()->where('guard_name', 'web')->whereIn(DB::raw('LOWER(name)'), $roles)
                ->get()->each(fn (Role $role) => $role->givePermissionTo($permissions));
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
