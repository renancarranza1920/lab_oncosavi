<?php

namespace App\Console\Commands;

use Database\Seeders\EntornoTestSeeder;
use Illuminate\Console\Command;

class PrepararEntornoTest extends Command
{
    protected $signature = 'oncosavi:preparar-test';
    protected $description = 'Prepara una base vacía y separada con catálogo, usuarios y documentos de ejemplo.';

    public function handle(): int
    {
        try {
            app(EntornoTestSeeder::class)->setCommand($this)->run();
        } catch (\LogicException $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }
        $this->info('Entorno de ejemplo preparado. No hay datos reales ni se envió correo o WhatsApp.');
        $this->info('Accesos privados en storage/app/private/usuarios-prueba.json y soporte-superadmin.json.');
        return self::SUCCESS;
    }
}
