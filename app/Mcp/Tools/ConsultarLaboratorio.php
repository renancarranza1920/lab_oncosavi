<?php

namespace App\Mcp\Tools;

use App\Services\Chatbot\InformesLaboratorio;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('consultar_laboratorio')]
#[Description('Obtiene tablas de informes administrativos: resumen, ordenes_por_estado, ingresos_por_dia, examenes_populares, ordenes_recientes y clientes_nuevos. Solo lectura de datos de negocio; registra la consulta en bitácora. Sin datos clínicos ni personales.')]
#[IsReadOnly]
class ConsultarLaboratorio extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'informe' => $schema->string()->enum(InformesLaboratorio::INFORMES)->required(),
            'desde' => $schema->string()->description('YYYY-MM-DD, fecha inicial inclusiva.'),
            'hasta' => $schema->string()->description('YYYY-MM-DD, fecha final inclusiva; máximo 366 días.'),
            'estado' => $schema->string()->enum(InformesLaboratorio::ESTADOS),
            'limite' => $schema->integer()->min(1)->max(20),
        ];
    }

    public function handle(Request $request, InformesLaboratorio $informes): Response|ResponseFactory
    {
        try {
            abort_unless(auth()->user(), 401);

            return Response::structured($informes->consultar($request->all(), auth()->user()));
        } catch (\Illuminate\Validation\ValidationException) {
            return Response::error('Parámetros no válidos. Use los informes permitidos, fechas YYYY-MM-DD y hasta 366 días.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException) {
            return Response::error('No tiene autorización para consultar este informe.');
        } catch (\Exception) {
            return Response::error('No fue posible consultar el informe.');
        }
    }
}
