<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesPermisosSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->generatePermissions();
        foreach (['ver_reporte_orden', 'descargar_reporte_orden', 'enviar_reporte_orden', 'enviar_cotizacion_whatsapp', 'gestionar_firma_sello_propio', 'ver_bitacora_completa', 'view_dashboard', 'dashboard_ordenes', 'dashboard_clientes', 'dashboard_estados', 'dashboard_examenes', 'dashboard_ultimas_ordenes', 'sincronizar_catalogo_orden', 'ver_catalogo_pdf'] as $nombre) {
            Permission::findOrCreate($nombre, 'web');
        }
        foreach (config('roles') as $nombre => $permisos) {
            foreach ($permisos as $permiso) {
                Permission::findOrCreate($permiso, 'web');
            }
            $roles = Role::where('guard_name', 'web')->whereRaw('LOWER(name) = ?', [strtolower($nombre)])->get();
            if ($roles->isEmpty()) {
                $roles->push(Role::findOrCreate($nombre, 'web'));
            }
            foreach ($roles as $rol) {
                $rol->syncPermissions($permisos);
            }
        }
        Role::findOrCreate('admin', 'web')->syncPermissions(Permission::where('guard_name', 'web')->get());
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function generatePermissions()
    {
        $resources = [
            'clientes',
            'examen',
            'orden',
            'perfil',
            'role',
            'tipo::examen',
            'user',
            'activity::log',
            'codigo',
            'cotizacion',
            'grupo::etario',
            'muestra',
            'prueba',
            'tipo::prueba',
            'reactivo',

        ];




        //////////////////////////////////////////////PERMISOS GRANULARES AUTOMÁTICOS/////////////////////////////////////////////////////

        //--- Permisos específicos para clientes ----//
        $clienteActions = [
            'ver_detalle_clientes',    // Para el botón 'ver-modal'
            'cambiar_estado_clientes', // Para el botón 'cambiar_estado'
            'ver_expediente_clientes', // Para el botón 'expediente'
        ];

        foreach ($clienteActions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        /// ///// Permisos Granulares para COTIZACIONES (Herramienta)
        $cotizacionActions = [
            'access_cotizaciones',      // Para poder ver el menú y entrar a la pantalla
            'generar_pdf_cotizacion',   // Para el botón de generar/imprimir el PDF
            'enviar_cotizacion_email',  // (Opcional) Si tienes botón de enviar por correo
        ];

        foreach ($cotizacionActions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        /////////// --- Permisos Granulares para EXÁMENES ---
        $examenActions = [
            'ver_detalle_examenes',     // Para botón 'ver-modal'
            'agregar_pruebas_examenes', // Para botón 'addPruebas'
            'cambiar_estado_examenes',  // Para botón 'cambiar_estado'
        ];

        foreach ($examenActions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        //////////////////// --- Permisos Granulares para ÓRDENES ---
        $ordenActions = [
            'procesar_muestras_orden',   // Para "Gestionar Muestras"
            'ingresar_resultados_orden', // Para "Ingresar Resultados"
            'imprimir_etiquetas_orden',  // Para "Imprimir Etiquetas"
            'ver_pruebas_orden',         // Para "Ver Pruebas"
            'pausar_orden',              // Para "Pausar"
            'reanudar_orden',            // Para "Reanudar"
            'finalizar_orden',           // Para "Finalizar"
            'generar_reporte_orden',     // Para "Generar Reporte PDF"
            'cancelar_orden',            // Para "Cancelar"
            'restaurar_orden',           // Para "Restaurar"
        ];

        foreach ($ordenActions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        ///// // --- Permisos Granulares para PERFILES ---
        $perfilActions = [
            'cambiar_estado_perfiles', // Para el botón 'toggleEstado'
        ];

        foreach ($perfilActions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        //// // --- Permisos Granulares para PRUEBAS ---
        $pruebaActions = [
            'ver_pruebas_conjuntas', // Para el botón de la cabecera "Ver Pruebas en Matriz"
            'editar_pruebas_conjuntas',
            'eliminar_pruebas_conjuntas',
            'cambiar_estado_pruebas',
        ];

        foreach ($pruebaActions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        /// // --- Permisos Granulares para TIPOS DE EXAMEN ---
        $tipoExamenActions = [
            'cambiar_estado_tipo_examenes', // Para el botón 'toggleEstado'
        ];

        foreach ($tipoExamenActions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        /// // --- Permisos Granulares para PÁGINAS ---
        $paginasActions = [
            'acceder_buscador_expedientes', // Para poder entrar al menú "Buscar Expediente"
        ];

        foreach ($paginasActions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        //// --- Permisos Granulares para KANBAN ETIQUETAS ---
        $kanbanActions = [
            'imprimir_etiquetas_kanban', // Para todos los botones de imprimir (ZPL)
            'mover_etiquetas_kanban',    // Para poder arrastrar y soltar tarjetas
        ];

        foreach ($kanbanActions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
        
                //// --- Permisos Granulares para GRUPO ETARIOS ---
        $grupoEtarios = [
            'cambiar_estado_grupos', // Para todos los botones de imprimir (ZPL)
                // Para poder arrastrar y soltar tarjetas
        ];

        foreach ($grupoEtarios as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
    
    
                   //// --- Permisos Granulares para WIDGETS ---
        $widget = [
            'ingresos_diarios', // Para todos los botones de imprimir (ZPL)
                // Para poder arrastrar y soltar tarjetas
        ];

        foreach ($widget as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
        //

        // Permisos por recurso
        foreach ($resources as $resource) {
            $permissions = [
                "view_{$resource}",
                "view_any_{$resource}",
                "create_{$resource}",
                "update_{$resource}",
                "restore_{$resource}",
                "restore_any_{$resource}",
                "replicate_{$resource}",
                "reorder_{$resource}",
                "delete_{$resource}",
                "delete_any_{$resource}",
                "force_delete_{$resource}",
                "force_delete_any_{$resource}",
            ];

            foreach ($permissions as $permission) {
                Permission::firstOrCreate(['name' => $permission]);
            }

            Log::info("Permisos generados para el recurso: {$resource}");
        }

        // Permisos especiales/globales
        $specialPermissions = [
            'impersonate_user',
            'access_admin_panel',
            'manage_settings',
            'export_data',
            'import_data',
            'view_reports',
        ];

        foreach ($specialPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        Log::info('Permisos especiales generados.', ['count' => count($specialPermissions)]);

        Log::info('Permisos totales generados automáticamente:', ['total' => Permission::count()]);
    }
}
