<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\ConsultarLaboratorio;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('ONCOSAVI · Informes del laboratorio')]
#[Version('1.0.0')]
#[Instructions('Consulta informes administrativos reales mediante consultar_laboratorio. No ejecuta SQL libre ni modifica datos de negocio. Acceso exclusivo del administrador autenticado. Fechas de El Salvador; importes en USD, no equivalen a pagos comprobados.')]
class LaboratorioServer extends Server
{
    protected array $tools = [ConsultarLaboratorio::class];
}
