<?php

namespace App\Console\Commands;

use App\Services\RepararAutoriaResultados as Servicio;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class RepararAutoriaResultados extends Command
{
    protected $signature = 'oncosavi:reparar-autoria-resultados
        {orden : ID de la orden afectada}
        {--desde-usuario= : ID del usuario que quedó asignado por error}
        {--ejecutar : Recuperar la autoría comprobada, con respaldo privado y bitácora}';

    protected $description = 'Recupera autores anteriores cuando un guardado solo cambió la autoría, sin modificar datos clínicos.';

    public function handle(Servicio $servicio): int
    {
        $orden = filter_var($this->argument('orden'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $usuario = filter_var($this->option('desde-usuario'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (! $orden || ! $usuario) {
            $this->error('Indique el ID de la orden y --desde-usuario con un ID válido.');

            return self::FAILURE;
        }
        $lock = Cache::lock('oncosavi:reparar-autoria-resultados:'.$orden, 3600);
        if (! $lock->get()) {
            $this->error('Ya hay una reparación en ejecución para esta orden.');

            return self::FAILURE;
        }
        try {
            $plan = $servicio->revisar($orden, $usuario);
            $this->table(['Resultado', 'Autor recuperable', 'Actividad de respaldo'], array_map(
                fn ($cambio) => [$cambio['resultado_id'], $cambio['autor'].' (#'.$cambio['autor_id'].')', $cambio['actividad_id']], $plan['cambios']));
            if ($plan['bloqueados']) {
                $this->table(['Resultado', 'Motivo de bloqueo'], array_map(
                    fn ($bloqueo) => [$bloqueo['resultado_id'], $bloqueo['motivo']], $plan['bloqueados']));
                $this->error('Revisión incompleta: no se modificó ningún resultado de la orden.');

                return self::FAILURE;
            }
            if (! $this->option('ejecutar')) {
                $this->info('Solo revisión: no se cambió ningún dato. Para reparar, active mantenimiento y repita con --ejecutar.');

                return self::SUCCESS;
            }
            $reparacion = $servicio->ejecutar($orden, $usuario);
            $this->info('Autorías restauradas: '.$reparacion['cantidad'].'. Valores clínicos y fechas conservados.');
            if ($reparacion['respaldo']) {
                $this->info('Respaldo privado: storage/app/private/'.$reparacion['respaldo']);
                $this->info('Ahora regenere el PDF de la orden con Incluir sellos y firmas activado.');
            }

            return self::SUCCESS;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            $this->error('La orden o el usuario indicado no existe. No se cambió ningún dato.');

            return self::FAILURE;
        } catch (\LogicException|\RuntimeException $error) {
            $this->error($error->getMessage());

            return self::FAILURE;
        } finally {
            $lock->release();
        }
    }
}
