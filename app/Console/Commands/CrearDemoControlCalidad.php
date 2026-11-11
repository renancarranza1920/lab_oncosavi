<?php

namespace App\Console\Commands;

use App\ControlCalidad\Services\DemoControlCalidadService;
use Illuminate\Console\Command;

class CrearDemoControlCalidad extends Command
{
    protected $signature = 'oncosavi:cci-demo-crear {--usuario= : ID de un usuario existente con permisos de mantenimiento y registro de CCI}';

    protected $description = 'Crea datos ficticios identificados [DEMO] para probar Control de Calidad Interno.';

    public function handle(DemoControlCalidadService $demo): int
    {
        $id = $this->option('usuario');
        if ($id !== null && (! ctype_digit((string) $id) || (int) $id < 1)) {
            $this->error('El valor de --usuario debe ser el ID de un usuario existente.');

            return self::FAILURE;
        }
        try {
            $counts = $demo->crear($id !== null ? (int) $id : null);
            $this->info($counts['creado'] ? 'Datos ficticios [DEMO] creados.' : 'La demostración ya existe. Se conservaron sus datos y cambios.');
            $this->line("{$counts['areas']} áreas, {$counts['productos']} productos, {$counts['lotes']} lotes, {$counts['pruebas']} pruebas, {$counts['registros']} registros y {$counts['lecturas']} lecturas.");
            $this->line('Para retirar estos datos: php artisan oncosavi:cci-demo-limpiar');

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
