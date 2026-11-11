<?php

namespace App\Console\Commands;

use App\ControlCalidad\Services\DemoControlCalidadService;
use Illuminate\Console\Command;

class LimpiarDemoControlCalidad extends Command
{
    protected $signature = 'oncosavi:cci-demo-limpiar';

    protected $description = 'Borra únicamente los datos reservados [DEMO] de Control de Calidad Interno.';

    public function handle(DemoControlCalidadService $demo): int
    {
        try {
            $counts = $demo->limpiar();
            $this->info('Datos ficticios [DEMO] retirados. Los datos reales se conservaron.');
            $this->line("{$counts['areas']} áreas, {$counts['productos']} productos, {$counts['lotes']} lotes, {$counts['pruebas']} pruebas, {$counts['registros']} registros y {$counts['lecturas']} lecturas retirados.");

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
