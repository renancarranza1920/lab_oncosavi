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
    public const REVIEW = 'revisar_control_calidad_interno';
    public const ASSIGN_OPERATOR = 'asignar_laboratorista_control_calidad_interno';

    public static function options(): array
    {
        return [
            self::VIEW => 'Acceder al módulo y ver el resumen',
            self::MAINTAIN => 'Administrar áreas, equipos, productos, lotes, pruebas y niveles',
            self::REGISTER => 'Registrar controles',
            self::EDIT => 'Editar registros',
            self::DELETE => 'Eliminar registros',
            self::ASSIGN_OPERATOR => 'Asignar un laboratorista distinto al usuario actual',
            self::CHARTS => 'Consultar gráficos',
            self::HISTORY => 'Consultar el historial',
            self::EXPORT => 'Descargar PDF y CSV',
            self::REVIEW => 'Revisar controles',
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
            'Acceso y consultas' => array_intersect_key($options, array_flip([self::VIEW, self::CHARTS, self::HISTORY, self::EXPORT])),
            'Mantenimiento' => array_intersect_key($options, array_flip([self::MAINTAIN])),
            'Registros y revisión' => array_intersect_key($options, array_flip([self::REGISTER, self::EDIT, self::DELETE, self::ASSIGN_OPERATOR, self::REVIEW])),
        ];
    }

    /** Add the module permissions without replacing any existing role grants. */
    public static function register(): void
    {
        DB::transaction(function (): void {
            $permissions = collect(self::all())->map(fn (string $name): Permission => Permission::findOrCreate($name, 'web'));
            Role::query()->where('guard_name', 'web')->whereRaw('LOWER(name) = ?', ['admin'])
                ->get()->each(fn (Role $role) => $role->givePermissionTo($permissions));
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
