<?php

namespace App\Console\Commands;

use App\QaDemo\QaDemoCatalogImporter;
use App\QaDemo\QaDemoSeeder;
use App\QaDemo\QaDemoService;
use Illuminate\Console\Command;

class QaDemoPreparar extends Command
{
    protected $signature = 'oncosavi:qa-demo-preparar
        {--catalogo-ejemplo : Usar un catálogo de demostración, sin consultar la base clínica}
        {--sin-ejemplos : Importar catálogo y preparar la base sin generar equipos ni mediciones ilustrativas}';

    protected $aliases = ['oncosavi:control-calidad-preparar'];

    protected $description = 'Prepara Control de Calidad Interno y actualiza su base privada conservando el historial.';

    public function handle(QaDemoService $qa, QaDemoCatalogImporter $catalog, QaDemoSeeder $seeder): int
    {
        try {
            // Leer primero el catálogo: una falla de importación no activa ni modifica la instalación de prueba.
            $example = (bool) $this->option('catalogo-ejemplo');
            $rows = $example ? $catalog->example() : $catalog->read();
            $qa->initialize();
            $count = $rows !== []
                ? $qa->importCatalog($rows, $example ? 'Catálogo de demostración' : 'Catálogo de química y hematología de Oncosavi')
                : count($qa->catalog());
            if ($rows === []) {
                $this->warn('El catálogo clínico no contiene pruebas disponibles. Puede crear las pruebas desde Mantenimiento.');
            }
            $seeded = ! $this->option('sin-ejemplos') && $seeder->seed($qa);
            $qa->activate();
            $this->info('Control de Calidad Interno preparado y activado. '.$count.' pruebas disponibles.');
            $this->line($seeded ? 'Se añadieron equipo, controles y mediciones ficticias para probar el módulo.' : 'Se conservaron los equipos, configuraciones y registros de QA existentes.');
            $base = rtrim((string) config('app.url'), '/').'/qa-quimica';
            $this->line('Resumen: '.$base);
            $this->line('Mantenimiento: '.$base.'/mantenimiento');
            $this->line('Registrar mediciones: '.$base.'/mediciones/nueva');
            $this->line('El acceso requiere la sesión de Oncosavi y los permisos de Control de Calidad Interno.');
            $this->line('El historial se conserva en almacenamiento privado. No se modificaron órdenes ni resultados clínicos.');
            return self::SUCCESS;
        } catch (\Throwable $exception) {
            report($exception);
            $this->error('No se pudo preparar Control de Calidad Interno: '.$exception->getMessage());
            return self::FAILURE;
        }
    }
}
