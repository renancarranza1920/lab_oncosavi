<?php

namespace App\Console\Commands;

use App\QaDemo\QaDemoService;
use Illuminate\Console\Command;

class QaDemoDesactivar extends Command
{
    protected $signature = 'oncosavi:qa-demo-desactivar';
    protected $description = 'Cierra la ruta pública de prueba QA sin borrar sus registros ni cambiar el laboratorio principal.';

    public function handle(QaDemoService $qa): int
    {
        try {
            $qa->deactivate();
            $this->info('La ruta de prueba QA quedó desactivada. Se conservaron todos sus registros.');
            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }
    }
}
