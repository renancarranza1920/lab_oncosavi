<?php

namespace App\ControlCalidad\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class VaciarControlCalidad
{
    public const TABLAS = [
        'cci_resultados', 'cci_registros', 'cci_valores_control', 'cci_niveles',
        'cci_pruebas', 'cci_lotes', 'cci_productos', 'cci_areas',
    ];

    public function ejecutar(): array
    {
        // Comparte el bloqueo con la demostración para que no se regenere durante la limpieza.
        $disk = Storage::disk('local');
        $path = $disk->path('cci-demo.lock');
        if (! is_dir(dirname($path)) && ! mkdir(dirname($path), 0700, true) && ! is_dir(dirname($path))) {
            throw new RuntimeException('No se pudo preparar el directorio privado de QA.');
        }
        $lock = fopen($path, 'c');
        if ($lock === false) {
            throw new RuntimeException('No se pudo abrir el bloqueo de QA.');
        }
        chmod($path, 0600);
        try {
            if (! flock($lock, LOCK_EX)) {
                throw new RuntimeException('No se pudo bloquear la limpieza de QA.');
            }
            $result = DB::transaction(function () use ($disk): array {
                $rows = [];
                $counts = [];
                foreach (self::TABLAS as $table) {
                    $rows[$table] = DB::table($table)->orderBy('id')->lockForUpdate()->get()->toArray();
                    $counts[$table] = count($rows[$table]);
                }
                if (array_sum($counts) === 0) {
                    return ['conteos' => $counts, 'respaldo' => null];
                }
                $file = 'cci-respaldos/antes-de-vaciar-'.now()->format('Ymd-His').'-'.bin2hex(random_bytes(4)).'.json';
                $backup = json_encode([
                    'version' => 1, 'fecha' => now()->toIso8601String(), 'tablas' => $rows,
                    'identificacion_demo' => $disk->exists('cci-demo.json') ? $disk->get('cci-demo.json') : null,
                ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                if (! $disk->put($file, $backup)) {
                    throw new RuntimeException('No se pudo guardar el respaldo privado. Se conservan los datos de QA.');
                }
                chmod($disk->path($file), 0600);
                // El orden mantiene activas las claves foráneas y la transacción evita borrados parciales.
                foreach (self::TABLAS as $table) {
                    DB::table($table)->delete();
                }

                return ['conteos' => $counts, 'respaldo' => $disk->path($file)];
            });
            if ($disk->exists('cci-demo.json') && ! $disk->delete('cci-demo.json')) {
                throw new RuntimeException('QA quedó vacío, pero no se pudo retirar la identificación de demostración. Revisa los permisos de storage/app/private.');
            }

            return $result;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
