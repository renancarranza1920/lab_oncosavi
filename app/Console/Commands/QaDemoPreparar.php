<?php

namespace App\Console\Commands;

use App\QaDemo\QaDemoCatalogImporter;
use App\QaDemo\QaDemoSeeder;
use App\QaDemo\QaDemoService;
use Illuminate\Console\Command;

class QaDemoPreparar extends Command
{
    protected $signature = 'oncosavi:qa-demo-preparar
        {--catalogo-ejemplo : Usar sólo cinco pruebas públicas ilustrativas, sin abrir la base clínica}
        {--sin-ejemplos : Importar catálogo y preparar la base sin generar equipos ni mediciones ilustrativas}';

    protected $description = 'Prepara y activa QA en una base SQLite privada; sólo lee nombres y unidades del catálogo principal.';

    public function handle(QaDemoService $qa, QaDemoCatalogImporter $catalog, QaDemoSeeder $seeder): int
    {
        try {
            // Leer primero el catálogo: una falla de importación no activa ni modifica la instalación de prueba.
            $example = (bool) $this->option('catalogo-ejemplo');
            $rows = $example ? $catalog->example() : $catalog->read();
            if ($rows === []) {
                $this->error('No se encontraron pruebas activas de química. La ruta de prueba no se activó.');
                return self::FAILURE;
            }
            $qa->initialize();
            $count = $qa->importCatalog($rows, $example ? 'Catálogo ilustrativo de cinco pruebas' : 'Copia de sólo lectura del catálogo de química de Oncosavi');
            $seeded = ! $this->option('sin-ejemplos') && $seeder->seed($qa);
            $qa->activate();
            $this->info('QA preparado y activado. '.$count.' pruebas disponibles en su copia privada del catálogo.');
            $this->line($seeded ? 'Se añadieron equipo, controles y mediciones ficticias para probar el módulo.' : 'Se conservaron los equipos, configuraciones y registros de QA existentes.');
            $base = rtrim((string) config('app.url'), '/').'/qa-quimica';
            $this->line('Resumen: '.$base);
            $this->line('Configuración: '.$base.'/configuracion');
            $this->line('Registrar mediciones: '.$base.'/mediciones/nueva');
            $this->line('Acceso público de demostración, sin contraseña. Los nombres registrados son declarados por quien captura.');
            $this->line('No se ejecutaron migraciones ni seeders de la base clínica. Los registros QA permanecen en storage privado.');
            return self::SUCCESS;
        } catch (\Throwable $exception) {
            report($exception);
            $this->error('No se pudo preparar QA: '.$exception->getMessage());
            return self::FAILURE;
        }
    }
}
