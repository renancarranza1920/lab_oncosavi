<?php

namespace App\Console\Commands;

use App\Services\ReiniciarDatosOperativos as Servicio;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ReiniciarDatosOperativos extends Command
{
    protected $signature = 'oncosavi:reiniciar-operaciones {--ejecutar : Vaciar los datos operativos y reiniciar sus IDs}';

    protected $description = 'Prepara el inicio de operaciones conservando personal, firmas, sellos, catálogos y acceso médico general.';

    public function handle(Servicio $servicio): int
    {
        $this->table(['Tabla', 'Registros que se retirarán'], collect($servicio->resumen())
            ->map(fn ($cantidad, $tabla) => [$tabla, $cantidad])->values()->all());
        if (!$this->option('ejecutar')) {
            $this->info('Solo revisión: no se cambió ningún dato. Para reiniciar use --ejecutar con la aplicación en mantenimiento.');

            return self::SUCCESS;
        }
        $lock = Cache::lock('oncosavi:reiniciar-operaciones', 3600);
        if (!$lock->get()) {
            $this->error('Ya hay un reinicio en ejecución.');

            return self::FAILURE;
        }
        try {
            $respaldo = $servicio->ejecutar();
            $this->info('Operaciones reiniciadas. Órdenes y clientes comienzan en ID 1; médicos registrados: 0.');
            $this->info('Usuarios, contraseñas, firmas, sellos, catálogos y acceso medicos conservados.');
            $this->info('Respaldo privado: storage/app/private/'.$respaldo);

            return self::SUCCESS;
        } catch (\Throwable $error) {
            $this->error($error->getMessage());

            return self::FAILURE;
        } finally {
            $lock->release();
        }
    }
}
