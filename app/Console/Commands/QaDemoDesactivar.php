<?php

namespace App\Console\Commands;

use App\QaDemo\QaDemoService;
use Illuminate\Console\Command;

class QaDemoDesactivar extends Command
{
    protected $signature = 'oncosavi:qa-demo-desactivar';
    protected $aliases = ['oncosavi:control-calidad-desactivar'];
    protected $description = 'Desactiva la ruta de Control de Calidad Interno sin borrar sus registros.';

    public function handle(QaDemoService $qa): int
    {
        try {
            $qa->deactivate();
            $this->info('Control de Calidad Interno quedó desactivado. Se conservaron todos sus registros.');
            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }
    }
}
