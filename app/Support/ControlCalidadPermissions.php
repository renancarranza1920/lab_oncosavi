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

    public const CREATE_AREA = 'crear_areas_control_calidad_interno';

    public const EDIT_AREA = 'editar_areas_control_calidad_interno';

    public const DELETE_AREA = 'eliminar_areas_control_calidad_interno';

    public const CREATE_PRODUCT = 'crear_productos_control_calidad_interno';

    public const EDIT_PRODUCT = 'editar_productos_control_calidad_interno';

    public const DELETE_PRODUCT = 'eliminar_productos_control_calidad_interno';

    public const CREATE_TEST = 'crear_pruebas_control_calidad_interno';

    public const EDIT_TEST = 'editar_pruebas_control_calidad_interno';

    public const DELETE_TEST = 'eliminar_pruebas_control_calidad_interno';

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
            self::MAINTAIN => 'Consultar mantenimiento: áreas, productos y pruebas',
            self::CREATE_AREA => 'Agregar áreas y equipos',
            self::EDIT_AREA => 'Editar áreas y equipos',
            self::DELETE_AREA => 'Eliminar áreas y equipos',
            self::CREATE_PRODUCT => 'Agregar productos y lotes',
            self::EDIT_PRODUCT => 'Editar productos y lotes',
            self::DELETE_PRODUCT => 'Eliminar productos y lotes',
            self::CREATE_TEST => 'Agregar pruebas y sus niveles',
            self::EDIT_TEST => 'Editar pruebas, niveles y valores de control',
            self::DELETE_TEST => 'Eliminar pruebas y sus niveles',
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
            'Mantenimiento · Áreas y equipos' => array_intersect_key($options, array_flip([self::CREATE_AREA, self::EDIT_AREA, self::DELETE_AREA])),
            'Mantenimiento · Productos y lotes' => array_intersect_key($options, array_flip([self::CREATE_PRODUCT, self::EDIT_PRODUCT, self::DELETE_PRODUCT])),
            'Mantenimiento · Pruebas y niveles' => array_intersect_key($options, array_flip([self::CREATE_TEST, self::EDIT_TEST, self::DELETE_TEST])),
            'Registros' => array_intersect_key($options, array_flip([self::REGISTER, self::EDIT, self::DELETE])),
            'Exportación' => array_intersect_key($options, array_flip([self::EXPORT])),
        ];
    }

    public static function maintenancePermission(string $section, string $action): string
    {
        $permissions = [
            'areas' => ['create' => self::CREATE_AREA, 'edit' => self::EDIT_AREA, 'delete' => self::DELETE_AREA],
            'productos' => ['create' => self::CREATE_PRODUCT, 'edit' => self::EDIT_PRODUCT, 'delete' => self::DELETE_PRODUCT],
            'pruebas' => ['create' => self::CREATE_TEST, 'edit' => self::EDIT_TEST, 'delete' => self::DELETE_TEST],
        ];

        return $permissions[$section][$action] ?? throw new \InvalidArgumentException('Acción de mantenimiento no válida.');
    }

    public static function maintenanceActions(): array
    {
        return [self::CREATE_AREA, self::EDIT_AREA, self::DELETE_AREA,
            self::CREATE_PRODUCT, self::EDIT_PRODUCT, self::DELETE_PRODUCT,
            self::CREATE_TEST, self::EDIT_TEST, self::DELETE_TEST];
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
