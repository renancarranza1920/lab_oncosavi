<?php

namespace App\ControlCalidad\Services;

use App\ControlCalidad\Models\Area;
use App\ControlCalidad\Models\Lote;
use App\ControlCalidad\Models\Nivel;
use App\ControlCalidad\Models\Producto;
use App\ControlCalidad\Models\Prueba;
use App\ControlCalidad\Models\Registro;
use App\ControlCalidad\Models\Resultado;
use App\ControlCalidad\Models\ValorControl;
use App\Models\User;
use App\Support\ControlCalidadPermissions as Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DemoControlCalidadService
{
    private const MANIFEST = 'cci-demo.json';

    private const TABLES = ['areas' => 'cci_areas', 'productos' => 'cci_productos', 'lotes' => 'cci_lotes'];

    public const AREAS = [
        ['nombre' => '[DEMO] Química', 'equipo' => 'CCI-DEMO-QUIMICA'],
        ['nombre' => '[DEMO] Hematología', 'equipo' => 'CCI-DEMO-HEMATOLOGIA'],
    ];

    public const PRODUCTOS = [
        '[DEMO] Suero de control multianalito',
        '[DEMO] Control hematológico BC-3D',
    ];

    public function crear(?int $usuarioId = null): array
    {
        $actor = $this->usuario($usuarioId);

        return $this->bloqueado(function () use ($actor): array {
            $manifest = $this->leerManifest();
            if ($manifest !== null) {
                $ids = $this->validarManifest($manifest);
                if ($ids !== null) {
                    return ['creado' => false] + $this->conteo($ids['areas'], $ids['productos'], $ids['lotes']);
                }
                $this->borrarManifest();
            }
            if ($this->hayIdentidadesReservadas()) {
                throw new RuntimeException('Existen elementos [DEMO] sin su identificación privada. No se modificaron datos. Conserva storage/app/private/cci-demo.json junto con la base de datos de la demostración.');
            }

            return DB::transaction(function () use ($actor): array {
                $service = app(ControlCalidadService::class);
                $areaIds = [];
                $productoIds = [];
                $loteIds = [];
                foreach (self::AREAS as $index => $identity) {
                    $area = Area::create($identity);
                    $areaIds[] = $area->id;
                    $producto = Producto::create(['nombre' => self::PRODUCTOS[$index]]);
                    $productoIds[] = $producto->id;
                    $lotes = [];
                    foreach (['Bajo', 'Medio', 'Alto'] as $level => $nombre) {
                        $lotes[] = Lote::create([
                            'producto_id' => $producto->id, 'nombre' => '[DEMO] Nivel '.$nombre,
                            'numero' => 'CCI-DEMO-'.($index === 0 ? 'Q' : 'H').'-'.($level + 1),
                            'caduca_el' => now()->addMonths(6)->endOfMonth()->toDateString(),
                        ]);
                        $loteIds[] = $lotes[$level]->id;
                    }
                    foreach ($this->pruebas($index) as [$nombre, $unidad, $rangos]) {
                        $niveles = [];
                        foreach ($rangos as $level => [$minimo, $maximo]) {
                            $niveles[] = [
                                'nombre' => ['Bajo', 'Medio', 'Alto'][$level], 'lote_id' => $lotes[$level]->id,
                                'minimo' => (string) $minimo, 'maximo' => (string) $maximo,
                            ];
                        }
                        $service->guardarPrueba([
                            'area_id' => $area->id, 'nombre' => $nombre, 'unidad' => $unidad, 'niveles' => $niveles,
                        ], $actor);
                    }
                    $niveles = Nivel::whereHas('prueba', fn ($query) => $query->where('area_id', $area->id))
                        ->with(['prueba', 'valorControlActual'])->orderBy('id')->get();
                    // La última medición de hoy repite el control después del ajuste simulado.
                    foreach ([...range(14, 0), -1] as $dia) {
                        $fueraDeRango = in_array($dia, [3, 0], true);
                        $corregido = in_array($dia, [2, -1], true);
                        $resultados = [];
                        foreach ($niveles as $position => $nivel) {
                            $control = $nivel->valorControlActual;
                            $center = (float) $control->esperado;
                            $halfRange = ((float) $control->maximo - (float) $control->minimo) / 2;
                            $factor = $fueraDeRango ? ($position % 2 === 0 ? 1.5 : -1.5)
                                : ($corregido ? 0 : [-0.22, 0.18, 0.05, 0.33, -0.10][($dia + $position) % 5]);
                            $value = round($center + $halfRange * $factor, 6);
                            $raw = rtrim(rtrim(number_format($value, 6, '.', ''), '0'), '.');
                            $resultados[] = ['nivel_id' => $nivel->id, 'valor' => str_replace('.', ',', $raw)];
                        }
                        $registro = $service->guardar([
                            'area_id' => $area->id, 'resultados' => $resultados,
                            'observaciones' => match (true) {
                                $fueraDeRango => '[DEMO] Medición fuera de rango antes del ajuste simulado del equipo.',
                                $corregido => '[DEMO] Nueva medición dentro de rango después del ajuste simulado del equipo.',
                                default => '[DEMO] Datos ficticios para pruebas de funcionalidad.',
                            },
                        ], $actor);
                        $fecha = $dia > 0 ? now()->subDays($dia)->setTime(7, 45) : now()->startOfMinute();
                        if ($dia === 0) {
                            $fecha = $fecha->subMinutes(15)->max(now()->startOfDay());
                        }
                        $registro->update(['fecha_hora' => $fecha]);
                    }
                }

                $this->guardarManifest(['areas' => $areaIds, 'productos' => $productoIds, 'lotes' => $loteIds]);

                return ['creado' => true] + $this->conteo($areaIds, $productoIds, $loteIds);
            }, 3);
        });
    }

    public function limpiar(): array
    {
        return $this->bloqueado(function (): array {
            $manifest = $this->leerManifest();
            if ($manifest === null) {
                if ($this->hayIdentidadesReservadas()) {
                    throw new RuntimeException('Existen elementos [DEMO] sin su identificación privada. No se borró ningún dato. Recupera storage/app/private/cci-demo.json del respaldo correspondiente.');
                }

                return $this->conteo([], [], []);
            }
            $counts = DB::transaction(function () use ($manifest): array {
                $ids = $this->validarManifest($manifest);
                if ($ids === null) {
                    return $this->conteo([], [], []);
                }
                $areaIds = $ids['areas'];
                $productoIds = $ids['productos'];
                $loteIds = Lote::where(function ($query) use ($ids, $productoIds): void {
                    $query->whereIn('id', $ids['lotes'])->orWhereIn('producto_id', $productoIds);
                })->pluck('id')->all();
                $pruebaIds = Prueba::whereIn('area_id', $areaIds)->pluck('id')->all();
                $nivelIds = Nivel::whereIn('prueba_id', $pruebaIds)->pluck('id')->all();
                $controlIds = ValorControl::where(function ($query) use ($nivelIds, $loteIds): void {
                    $query->whereIn('nivel_id', $nivelIds)->orWhereIn('lote_id', $loteIds);
                })->pluck('id')->all();

                $usedOutsideDemo = ValorControl::whereIn('lote_id', $loteIds)
                    ->whereHas('nivel.prueba', fn ($query) => $query->whereNotIn('area_id', $areaIds))->exists();
                $foreignResults = DB::table('cci_resultados as resultado')
                    ->join('cci_registros as registro', 'registro.id', '=', 'resultado.registro_id')
                    ->whereNotIn('registro.area_id', $areaIds)
                    ->where(function ($query) use ($pruebaIds, $nivelIds, $controlIds): void {
                        $query->whereIn('resultado.prueba_id', $pruebaIds)
                            ->orWhereIn('resultado.nivel_id', $nivelIds)->orWhereIn('resultado.valor_control_id', $controlIds);
                    })->exists();
                if ($usedOutsideDemo || $foreignResults) {
                    throw new RuntimeException('No se borró ningún dato: un lote o nivel [DEMO] está asociado a un área real. Sustituye esa referencia por el control real antes de limpiar la demostración.');
                }

                $counts = $this->conteo($areaIds, $productoIds, $loteIds);
                $registroIds = Registro::withTrashed()->whereIn('area_id', $areaIds)->pluck('id')->all();
                Resultado::whereIn('registro_id', $registroIds)->delete();
                Registro::withTrashed()->whereIn('id', $registroIds)->forceDelete();
                Prueba::whereIn('id', $pruebaIds)->delete();
                Area::whereIn('id', $areaIds)->delete();
                Lote::whereIn('id', $loteIds)->delete();
                Producto::whereIn('id', $productoIds)->delete();

                return $counts;
            }, 3);
            $this->borrarManifest();

            return $counts;
        });
    }

    private function hayIdentidadesReservadas(): bool
    {
        return Area::whereIn('equipo', array_column(self::AREAS, 'equipo'))
            ->orWhereIn('nombre', array_column(self::AREAS, 'nombre'))->exists()
            || Producto::whereIn('nombre', self::PRODUCTOS)->exists();
    }

    private function usuario(?int $id): User
    {
        $candidates = User::query()->when($id !== null, fn ($query) => $query->whereKey($id))->orderBy('id')->get();
        $candidates = $candidates->sortBy(fn (User $user): int => $user->roles->contains(fn ($role) => mb_strtolower($role->name) === 'admin') ? 0 : 1);
        foreach ($candidates as $user) {
            if ($user->can('access_admin_panel') && $user->can(Permission::VIEW)
                && $user->can(Permission::MAINTAIN) && $user->hasAllPermissions([Permission::CREATE_AREA, Permission::CREATE_PRODUCT, Permission::CREATE_TEST])
                && $user->can(Permission::REGISTER)) {
                return $user;
            }
        }

        throw new RuntimeException('No se encontró un usuario autorizado para administrar y registrar controles. Asigna los permisos de CCI a un usuario existente o indica su ID con --usuario=ID.');
    }

    private function conteo(array $areaIds, array $productoIds, array $loteIds): array
    {
        $registros = Registro::withTrashed()->whereIn('area_id', $areaIds)->pluck('id');

        return [
            'areas' => count($areaIds),
            'productos' => Producto::whereIn('id', $productoIds)->count(),
            'lotes' => Lote::where(function ($query) use ($loteIds, $productoIds): void {
                $query->whereIn('id', $loteIds)->orWhereIn('producto_id', $productoIds);
            })->count(),
            'pruebas' => Prueba::whereIn('area_id', $areaIds)->count(),
            'niveles' => Nivel::whereHas('prueba', fn ($query) => $query->whereIn('area_id', $areaIds))->count(),
            'registros' => $registros->count(),
            'lecturas' => Resultado::whereIn('registro_id', $registros)->count(),
        ];
    }

    private function bloqueado(callable $operation): array
    {
        $path = Storage::disk('local')->path('cci-demo.lock');
        if (! is_dir(dirname($path)) && ! mkdir(dirname($path), 0700, true) && ! is_dir(dirname($path))) {
            throw new RuntimeException('No se pudo preparar la identificación privada de la demostración.');
        }
        $lock = fopen($path, 'c');
        if ($lock === false) {
            throw new RuntimeException('No se pudo abrir el bloqueo de la demostración.');
        }
        chmod($path, 0600);
        try {
            if (! flock($lock, LOCK_EX)) {
                throw new RuntimeException('No se pudo bloquear la demostración. Inténtalo nuevamente.');
            }

            return $operation();
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function contexto(): string
    {
        $db = DB::connection();
        $context = ['connection' => $db->getName()];
        foreach (['driver', 'host', 'port', 'database', 'prefix'] as $key) {
            $context[$key] = $db->getConfig($key);
        }

        return hash('sha256', json_encode($context, JSON_THROW_ON_ERROR));
    }

    private function guardarManifest(array $ids): void
    {
        $items = [];
        foreach (self::TABLES as $type => $table) {
            $items[$type] = DB::table($table)->whereIn('id', $ids[$type])->orderBy('id')
                ->get(['id', 'created_at'])->map(fn ($row): array => ['id' => (int) $row->id, 'created_at' => (string) $row->created_at])->all();
        }
        $json = json_encode(['version' => 1, 'context' => $this->contexto(), 'items' => $items], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        $disk = Storage::disk('local');
        $temporary = self::MANIFEST.'.'.bin2hex(random_bytes(5)).'.tmp';
        if (! $disk->put($temporary, $json)) {
            throw new RuntimeException('No se pudo guardar la identificación privada. No se crearon datos de demostración.');
        }
        chmod($disk->path($temporary), 0600);
        if (! rename($disk->path($temporary), $disk->path(self::MANIFEST))) {
            $disk->delete($temporary);
            throw new RuntimeException('No se pudo guardar la identificación privada. No se crearon datos de demostración.');
        }
    }

    private function leerManifest(): ?array
    {
        $disk = Storage::disk('local');
        if (! $disk->exists(self::MANIFEST)) {
            return null;
        }
        try {
            $manifest = json_decode($disk->get(self::MANIFEST), true, 32, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            throw new RuntimeException('La identificación privada de la demostración está dañada. No se modificó ningún dato.');
        }
        if (! is_array($manifest) || ($manifest['version'] ?? null) !== 1
            || ($manifest['context'] ?? '') !== $this->contexto() || ! is_array($manifest['items'] ?? null)) {
            throw new RuntimeException('La identificación privada no corresponde a esta base de datos. No se modificó ningún dato.');
        }

        return $manifest;
    }

    /** Missing all original rows means a rolled-back creation or an already completed cleanup. */
    private function validarManifest(array $manifest): ?array
    {
        $ids = [];
        $missing = 0;
        $total = 0;
        foreach (self::TABLES as $type => $table) {
            $items = $manifest['items'][$type] ?? null;
            if (! is_array($items) || $items === []) {
                throw new RuntimeException('La identificación privada de la demostración está incompleta. No se modificó ningún dato.');
            }
            $ids[$type] = [];
            foreach ($items as $item) {
                if (! is_array($item) || ! is_int($item['id'] ?? null) || $item['id'] < 1 || ! is_string($item['created_at'] ?? null)
                    || in_array($item['id'], $ids[$type], true)) {
                    throw new RuntimeException('La identificación privada de la demostración es inválida. No se modificó ningún dato.');
                }
                $ids[$type][] = $item['id'];
                $total++;
                $row = DB::table($table)->where('id', $item['id'])->lockForUpdate()->first(['created_at']);
                if ($row === null) {
                    $missing++;
                } elseif ((string) $row->created_at !== $item['created_at']) {
                    throw new RuntimeException('Un identificador de demostración pertenece ahora a otro registro. No se modificó ningún dato.');
                }
            }
        }
        if ($missing === $total) {
            return null;
        }
        if ($missing !== 0) {
            throw new RuntimeException('La base de demostración ya no coincide con su identificación privada. No se modificó ningún dato.');
        }

        return $ids;
    }

    private function borrarManifest(): void
    {
        if (! Storage::disk('local')->delete(self::MANIFEST)) {
            throw new RuntimeException('No se pudo retirar la identificación privada de demostración. Intenta ejecutar la limpieza nuevamente.');
        }
    }

    private function pruebas(int $area): array
    {
        return $area === 0 ? [
            ['GLUCOSA', 'mg/dL', [[54, 66], [99, 121], [270, 330]]],
            ['CREATININA', 'mg/dL', [[0.64, 0.96], [1.53, 2.07], [3.825, 5.175]]],
            ['COLESTEROL TOTAL', 'mg/dL', [[90, 110], [180, 220], [270, 330]]],
            ['GAMMA GLUTAMIL TRANSPEPTIDASA (GGT)', 'U/L', [[20, 30], [59.5, 80.5], [153, 207]]],
        ] : [
            ['LEUCOCITOS (WBC)', '10^9/L', [[1.5, 2.5], [6.7, 8.7], [18.4, 23.4]]],
            ['ERITROCITOS (RBC)', '10^12/L', [[2.00, 2.40], [4.23, 4.79], [5.37, 6.07]]],
            ['HEMOGLOBINA (HGB)', 'g/dL', [[5.7, 6.7], [13.0, 14.6], [17.9, 19.9]]],
            ['PLAQUETAS (PLT)', '10^9/L', [[30, 90], [183, 293], [428, 568]]],
        ];
    }
}
