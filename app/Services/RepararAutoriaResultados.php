<?php

namespace App\Services;

use App\Models\Actividad;
use App\Models\DetalleOrden;
use App\Models\Orden;
use App\Models\RegistroSoporte;
use App\Models\Resultado;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RepararAutoriaResultados
{
    public function revisar(int $ordenId, int $desdeUsuario): array
    {
        Orden::findOrFail($ordenId);
        User::findOrFail($desdeUsuario);

        return $this->preparar($ordenId, $desdeUsuario);
    }

    public function ejecutar(int $ordenId, int $desdeUsuario): array
    {
        if (! app()->environment('testing') && ! app()->isDownForMaintenance()) {
            throw new \LogicException('Primero active mantenimiento con php artisan down.');
        }
        $conexion = (new Resultado)->getConnection();
        if ($conexion->getName() !== (new Actividad)->getConnection()->getName()) {
            throw new \LogicException('La reparación requiere resultados y bitácora en la misma conexión para poder revertir juntos ante un fallo.');
        }
        if (! config('activitylog.enabled') || config('filesystems.disks.local.driver') !== 'local') {
            throw new \LogicException('Se requiere bitácora activa y almacenamiento privado local para el respaldo.');
        }

        return $conexion->transaction(function () use ($ordenId, $desdeUsuario): array {
            Orden::query()->lockForUpdate()->findOrFail($ordenId);
            User::findOrFail($desdeUsuario);
            // Volver a consultar bajo bloqueo: no aplicar una revisión desactualizada.
            $plan = $this->preparar($ordenId, $desdeUsuario, bloquear: true);
            if ($plan['bloqueados']) {
                throw new \LogicException('Hay resultados sin evidencia suficiente o con cambios clínicos. No se modificó ninguna autoría; revise primero sin --ejecutar.');
            }
            if (! $plan['cambios']) {
                return ['cantidad' => 0, 'respaldo' => null];
            }

            $respaldo = $this->respaldar($ordenId, $desdeUsuario, $plan);
            foreach ($plan['cambios'] as $cambio) {
                // Solo user_id; conservar valores, snapshots y fechas originales.
                $cantidad = Resultado::query()->whereKey($cambio['resultado_id'])
                    ->where('user_id', $desdeUsuario)->toBase()->update(['user_id' => $cambio['autor_id']]);
                if ($cantidad !== 1) {
                    throw new \RuntimeException('La autoría cambió durante la reparación. Se revirtieron los cambios.');
                }
                $registro = activity('Resultados')
                    // Ejecución de consola: no atribuir la reparación a un autor
                    // clínico ni conservar un causer precargado por el logger.
                    ->tap(fn (Actividad $actividad) => $actividad->causer()->dissociate())
                    ->performedOn(Resultado::findOrFail($cambio['resultado_id']))
                    ->event('autoria_restaurada')
                    ->withProperties([
                        'origen' => 'oncosavi:reparar-autoria-resultados', 'orden_id' => $ordenId,
                        'old' => ['user_id' => $desdeUsuario], 'attributes' => ['user_id' => $cambio['autor_id']],
                        'actividad_evidencia_id' => $cambio['actividad_id'], 'respaldo' => $respaldo,
                    ])->log('Autoría de resultado restaurada desde la bitácora, sin modificar datos clínicos.');
                if (! $registro) {
                    throw new \RuntimeException('No se pudo registrar la reparación en la bitácora. Se revirtieron los cambios.');
                }
            }

            return ['cantidad' => count($plan['cambios']), 'respaldo' => $respaldo];
        });
    }

    private function preparar(int $ordenId, int $desdeUsuario, bool $bloquear = false): array
    {
        $consulta = Resultado::query()
            ->whereIn('detalle_orden_id', DetalleOrden::query()->where('orden_id', $ordenId)->select('id'))
            ->where('user_id', $desdeUsuario)->orderBy('id');
        if ($bloquear) {
            $consulta->lockForUpdate();
        }
        $resultados = $consulta->get();
        $historial = $this->historial($resultados->modelKeys());
        $plan = ['cambios' => [], 'bloqueados' => [], 'originales' => []];
        foreach ($resultados as $resultado) {
            try {
                $plan['cambios'][] = $this->recuperar($resultado, $historial[$resultado->id] ?? [], $desdeUsuario);
                $plan['originales'][] = $resultado->getRawOriginal();
            } catch (\LogicException $error) {
                $plan['bloqueados'][] = ['resultado_id' => $resultado->id, 'motivo' => $error->getMessage()];
            }
        }

        return $plan;
    }

    private function historial(array $ids): array
    {
        if (! $ids) {
            return [];
        }
        $tipo = (new Resultado)->getMorphClass();
        $eventos = Actividad::query()->where('subject_type', $tipo)->whereIn('subject_id', $ids)->get()
            ->map(fn ($actividad) => array_replace($actividad->getAttributes(), ['properties' => $actividad->properties->all()]))->all();
        // Los eventos de soporte conservan el subject y los cambios en el archivo
        // privado, aunque la bitácora general solo muestre «Ajuste de soporte técnico».
        if ((new RegistroSoporte)->getConnection()->getSchemaBuilder()->hasTable('registros_soporte')) {
            $privados = RegistroSoporte::query()->where('datos->subject_type', $tipo)->whereIn('datos->subject_id', $ids)
                ->whereIn('activity_id', Actividad::query()->select('id')->where('event', Actividad::EVENTO_SOPORTE))->get();
            foreach ($privados as $registro) {
                $eventos[] = array_replace($registro->datos, ['id' => $registro->activity_id]);
            }
        }
        usort($eventos, fn ($a, $b) => (int) $a['id'] <=> (int) $b['id']);
        $historial = [];
        foreach ($eventos as $evento) {
            $historial[(int) $evento['subject_id']][] = $evento;
        }

        return $historial;
    }

    private function recuperar(Resultado $resultado, array $historial, int $desdeUsuario): array
    {
        // Los IDs se reiniciaron al abrir operaciones. No mezclar eventos de
        // resultados antiguos con otra fila que reutilizó el mismo ID.
        $creaciones = array_keys(array_filter($historial, function ($evento) use ($resultado): bool {
            $atributos = $evento['properties']['attributes'] ?? [];

            return ($evento['event'] ?? null) === 'created'
                && (int) ($atributos['id'] ?? 0) === $resultado->id
                && ! empty($atributos['created_at'])
                && $resultado->created_at->equalTo(Carbon::parse($atributos['created_at']));
        }));
        if (count($creaciones) !== 1) {
            throw new \LogicException('Falta un registro de creación inequívoco del resultado actual.');
        }
        $historial = array_slice($historial, $creaciones[0]);
        // Las descargas repetidas de la versión anterior podían dejar después
        // eventos que solo actualizaban la fecha, sin cambiar otra vez user_id.
        $indice = array_key_last($historial);
        while ($indice !== null && $indice > 0) {
            $evento = $historial[$indice];
            $campos = array_unique(array_merge(array_keys($evento['properties']['attributes'] ?? []), array_keys($evento['properties']['old'] ?? [])));
            if (($evento['event'] ?? null) !== 'updated' || (int) ($evento['causer_id'] ?? 0) !== $desdeUsuario
                || ($evento['causer_type'] ?? null) !== (new User)->getMorphClass()
                || $campos !== ['updated_at']) {
                break;
            }
            $indice--;
        }
        $ultimo = $indice !== null ? $historial[$indice] : null;
        $propiedades = $ultimo['properties'] ?? [];
        $nuevos = $propiedades['attributes'] ?? [];
        $anteriores = $propiedades['old'] ?? [];
        $autorId = (int) ($anteriores['user_id'] ?? 0);
        if (($ultimo['event'] ?? null) !== 'updated'
            || (int) ($ultimo['causer_id'] ?? 0) !== $desdeUsuario
            || ($ultimo['causer_type'] ?? null) !== (new User)->getMorphClass()
            || (int) ($nuevos['user_id'] ?? 0) !== $desdeUsuario
            || ! $autorId || $autorId === $desdeUsuario) {
            throw new \LogicException('Falta un cambio de autoría registrado que identifique al autor anterior.');
        }
        $campos = array_unique(array_merge(array_keys($nuevos), array_keys($anteriores)));
        if (array_diff($campos, ['user_id', 'updated_at'])) {
            throw new \LogicException('El último guardado también cambió datos clínicos; no se reasignará automáticamente.');
        }
        // Reconstruir los valores registrados y contrastarlos con la fila actual.
        // Esto detecta también modificaciones por SQL que no dejaron bitácora.
        $estado = [
            'valor_referencia_externo' => null, 'observaciones' => null, 'fuera_de_rango' => 0,
            'es_externo' => 0, 'alertar' => 0, 'prueba_nombre_snapshot' => null,
            'valor_referencia_snapshot' => null, 'unidades_snapshot' => null, 'prueba_id' => null,
        ];
        foreach ($historial as $evento) {
            if (! in_array($evento['event'] ?? null, ['created', 'updated', 'autoria_restaurada'], true)) {
                throw new \LogicException('Hay eventos que impiden comprobar la continuidad del resultado.');
            }
            if ($evento['id'] === $ultimo['id'] && (int) ($estado['user_id'] ?? 0) !== $autorId) {
                throw new \LogicException('El autor anterior no coincide con el historial del resultado.');
            }
            $estado = array_replace($estado, $evento['properties']['attributes'] ?? []);
        }
        foreach ($resultado->getRawOriginal() as $campo => $valor) {
            if (in_array($campo, ['id', 'created_at'], true)) {
                continue;
            }
            if (! array_key_exists($campo, $estado)) {
                throw new \LogicException('El contenido actual no coincide con el historial; requiere revisión manual.');
            }
            $coincide = match ($campo) {
                'updated_at' => $valor && $estado[$campo]
                    && Carbon::parse($valor, config('app.timezone'))->equalTo(Carbon::parse($estado[$campo])),
                'alertar', 'es_externo', 'fuera_de_rango' => (bool) $valor === (bool) $estado[$campo],
                default => $this->iguales($valor, $estado[$campo]),
            };
            if (! $coincide) {
                throw new \LogicException('El contenido actual no coincide con el historial; requiere revisión manual.');
            }
        }
        $autor = User::find($autorId);
        if (! $autor) {
            throw new \LogicException('El autor anterior ya no existe.');
        }

        return ['resultado_id' => $resultado->id, 'autor_id' => $autorId, 'autor' => $autor->name, 'actividad_id' => (int) $ultimo['id']];
    }

    private function iguales(mixed $a, mixed $b): bool
    {
        if ($a === null || $b === null) {
            return $a === $b;
        }

        return is_scalar($a) && is_scalar($b) && (string) $a === (string) $b;
    }

    private function respaldar(int $ordenId, int $desdeUsuario, array $plan): string
    {
        $ruta = 'reparaciones/autoria-resultados/'.now()->format('Ymd-His').'-'.Str::uuid().'/datos.json';
        $datos = json_encode([
            'fecha' => now()->toIso8601String(), 'orden_id' => $ordenId, 'desde_usuario' => $desdeUsuario,
            'resultados_antes' => $plan['originales'], 'cambios_previstos' => $plan['cambios'],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $disco = Storage::disk('local');
        if (! $disco->put($ruta, $datos)) {
            throw new \RuntimeException('No se pudo guardar el respaldo privado. No se modificó ninguna autoría.');
        }
        if (! chmod(dirname($disco->path($ruta)), 0700) || ! chmod($disco->path($ruta), 0600)) {
            throw new \RuntimeException('No se pudo proteger el respaldo privado. No se modificó ninguna autoría.');
        }

        return $ruta;
    }
}
