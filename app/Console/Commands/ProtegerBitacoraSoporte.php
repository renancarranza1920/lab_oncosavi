<?php

namespace App\Console\Commands;

use App\Models\Actividad;
use App\Support\Bitacora;
use Illuminate\Console\Command;

class ProtegerBitacoraSoporte extends Command
{
    protected $signature = 'oncosavi:proteger-bitacora-soporte';

    protected $description = 'Reserva los detalles de eventos anteriores de superadministradores, conservando su auditoría.';

    public function handle(): int
    {
        $cantidad = 0;
        Bitacora::reservados(Actividad::query())
            ->where(fn ($q) => $q->whereNull('event')->orWhere('event', '!=', Actividad::EVENTO_SOPORTE))
            ->chunkById(100, function ($registros) use (&$cantidad): void {
                foreach ($registros as $registro) {
                    if ($registro->protegerRegistroExistente()) {
                        $cantidad++;
                    }
                }
            });
        $this->info("Registros reservados: {$cantidad}. Fechas y auditoría conservadas; datos clínicos sin cambios.");

        return self::SUCCESS;
    }
}
