<?php

namespace App\Services;

use App\Models\Actividad;
use App\Models\Cliente;
use App\Models\ClienteTelefono;
use App\Models\DetalleOrden;
use App\Models\DetalleOrdenPerfil;
use App\Models\Medico;
use App\Models\Orden;
use App\Models\Resultado;
use App\Support\SesionPortalMedico;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReiniciarDatosOperativos
{
    // Dependientes primero. No incluye personal, catálogos, permisos ni bitácoras.
    public const TABLAS = ['resultados', 'detalle_orden', 'detalle_orden_perfils', 'ordens', 'cliente_telefonos', 'clientes', 'medicos'];

    public function resumen(): array
    {
        $resumen = [];
        foreach (self::TABLAS as $tabla) {
            $consulta = DB::table($tabla);
            if ($tabla === 'medicos') {
                $consulta->where(fn ($query) => $query->whereNull('portal_usuario')->orWhere('portal_usuario', '!=', 'medicos'));
            }
            $resumen[$tabla] = $consulta->count();
        }

        return $resumen;
    }

    public function ejecutar(): string
    {
        if (!app()->environment('testing') && !app()->isDownForMaintenance()) {
            throw new \LogicException('Primero active mantenimiento con php artisan down.');
        }
        $conexion = DB::connection();
        $driver = $conexion->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb', 'sqlite'], true)) {
            throw new \LogicException('El reinicio está preparado para MySQL, MariaDB y SQLite.');
        }
        if (config('filesystems.disks.local.driver') !== 'local' || config('filesystems.disks.public.driver') !== 'local') {
            throw new \LogicException('El reinicio requiere el almacenamiento local persistente del proyecto.');
        }
        $this->verificarDependencias();

        $local = Storage::disk('local');
        $respaldo = 'reinicios/'.now()->format('Ymd-His').'-'.Str::uuid();
        $datos = ['fecha' => now()->toIso8601String(), 'tablas' => []];
        $tablasRespaldo = self::TABLAS;
        if ($conexion->getSchemaBuilder()->hasTable('envios_whatsapp')) {
            // Una instalación anterior puede conservar este historial aunque el módulo ya no exista.
            $tablasRespaldo[] = 'envios_whatsapp';
        }
        foreach ($tablasRespaldo as $tabla) {
            $datos['tablas'][$tabla] = DB::table($tabla)->orderBy('id')->get()->all();
        }
        if (!$local->put($respaldo.'/datos.json', json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR))) {
            throw new \RuntimeException('No se pudo guardar el respaldo. No se borró ningún registro.');
        }
        if (!chmod($local->path($respaldo), 0700) || !chmod($local->path($respaldo.'/datos.json'), 0600)) {
            throw new \RuntimeException('No se pudo proteger el respaldo. No se borró ningún registro.');
        }
        $general = DB::table('medicos')->where('portal_usuario', 'medicos')->first();
        $reportes = Storage::disk('public')->path('reportes');
        $archivoReportes = $local->path($respaldo.'/reportes');
        $archivado = false;
        $datosEliminados = false;
        try {
            if (is_dir($reportes)) {
                if (!rename($reportes, $archivoReportes)) {
                    throw new \RuntimeException('No se pudieron archivar los PDFs anteriores. No se borró ningún registro.');
                }
                $archivado = true;
            }
            // Revoca solamente las sesiones médicas, sin cambiar contraseñas ni sesiones del personal.
            SesionPortalMedico::invalidar();
            $conexion->transaction(function () use ($general): void {
                $this->desvincularAuditoriaAnterior();
                foreach (self::TABLAS as $tabla) {
                    DB::table($tabla)->delete();
                }
                if ($general) {
                    // El acceso compartido ocupa el ID interno 1; no aparece en la lista de médicos.
                    DB::table('medicos')->insert(array_replace((array) $general, ['id' => 1]));
                }
            });
            $datosEliminados = true;
            foreach (self::TABLAS as $tabla) {
                if ($driver === 'sqlite') {
                    DB::table('sqlite_sequence')->where('name', $conexion->getTablePrefix().$tabla)->delete();
                } else {
                    $nombre = $conexion->getQueryGrammar()->wrapTable($tabla);
                    $conexion->statement("ALTER TABLE {$nombre} AUTO_INCREMENT = 1");
                }
            }
            activity('Administración')->event('reinicio_operativo')
                ->withProperties(['ejecucion' => 'Consola',
                    'registros_retirados' => array_map('count', array_intersect_key($datos['tablas'], array_flip(self::TABLAS))),
                    'envios_whatsapp_conservados' => count($datos['tablas']['envios_whatsapp'] ?? []), 'respaldo' => $respaldo])
                ->log('Reinicio de órdenes, clientes y médicos para iniciar operaciones');
        } catch (\Throwable $error) {
            if (!$datosEliminados && $archivado && !rename($archivoReportes, $reportes)) {
                throw new \RuntimeException('La base conservó sus datos; restaure los PDFs desde el respaldo '.$respaldo, previous: $error);
            }
            throw $error;
        }

        return $respaldo;
    }

    private function verificarDependencias(): void
    {
        $schema = DB::connection()->getSchemaBuilder();
        $nombreSchema = $schema->getCurrentSchemaName();
        foreach ($schema->getTables($nombreSchema) as $tabla) {
            if (in_array($tabla['name'], self::TABLAS, true)) {
                continue;
            }
            foreach ($schema->getForeignKeys($tabla['name']) as $clave) {
                if (in_array($clave['foreign_table'], self::TABLAS, true)
                    && ($clave['foreign_schema'] === null || $clave['foreign_schema'] === $nombreSchema)) {
                    if ($tabla['name'] === 'envios_whatsapp' && $clave['foreign_table'] === 'ordens'
                        && $clave['columns'] === ['orden_id'] && $clave['foreign_columns'] === ['id']
                        && strtolower($clave['on_delete']) === 'set null') {
                        // La FK histórica deja orden_id vacío al borrar la orden: conserva el envío
                        // y evita asociarlo al reutilizar el ID. No se crea ni se borra esta tabla.
                        continue;
                    }
                    throw new \LogicException('La tabla '.$tabla['name'].' depende de los datos operativos y no está incluida. No se borró ningún registro.');
                }
            }
        }
    }

    private function desvincularAuditoriaAnterior(): void
    {
        $tipos = array_map(fn ($clase) => (new $clase)->getMorphClass(), [Cliente::class, ClienteTelefono::class, Orden::class,
            DetalleOrden::class, DetalleOrdenPerfil::class, Resultado::class, Medico::class]);
        $medico = (new Medico)->getMorphClass();
        Actividad::query()->where(fn ($query) => $query->whereIn('subject_type', $tipos)->orWhere('causer_type', $medico))
            ->chunkById(200, function ($registros) use ($tipos, $medico): void {
                foreach ($registros as $registro) {
                    $cambios = [];
                    $referencia = [];
                    if (in_array($registro->subject_type, $tipos, true) && $registro->subject_id !== null) {
                        $referencia['modelo'] = $registro->subject_type;
                        $referencia['id'] = $registro->subject_id;
                        $cambios['subject_id'] = null;
                    }
                    if ($registro->causer_type === $medico && $registro->causer_id !== null) {
                        $referencia['medico_id'] = $registro->causer_id;
                        $referencia['medico_nombre'] = $registro->causer?->nombre;
                        $cambios['causer_id'] = null;
                    }
                    if ($cambios) {
                        $properties = $registro->properties->put('referencia_anterior_al_reinicio', $referencia);
                        $cambios['properties'] = $properties->toJson(JSON_THROW_ON_ERROR);
                        $registro->getConnection()->table($registro->getTable())->where('id', $registro->id)->update($cambios);
                    }
                }
            });
    }
}
