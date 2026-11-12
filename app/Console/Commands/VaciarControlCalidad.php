<?php

namespace App\Console\Commands;

use App\ControlCalidad\Services\VaciarControlCalidad as Limpieza;
use Illuminate\Console\Command;

class VaciarControlCalidad extends Command
{
    protected $signature = 'oncosavi:cci-vaciar {--force : Autoriza vaciar toda la configuración y las mediciones de QA}';

    protected $description = 'Deja QA vacío para su configuración manual y guarda un respaldo privado.';

    public function handle(Limpieza $limpieza): int
    {
        if (! $this->option('force')) {
            $this->error('Este comando vacía áreas, productos, lotes, pruebas, niveles y mediciones de QA. Para ejecutarlo usa --force.');

            return self::FAILURE;
        }
        try {
            $result = $limpieza->ejecutar();
            $this->info('QA quedó vacío y listo para la configuración manual.');
            $this->line('Se conservaron los usuarios, permisos y datos clínicos del laboratorio.');
            if ($result['respaldo']) {
                $this->line('Respaldo privado: '.$result['respaldo']);
            }

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
