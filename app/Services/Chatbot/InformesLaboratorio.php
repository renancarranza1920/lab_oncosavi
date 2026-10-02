<?php

namespace App\Services\Chatbot;

use App\Models\Cliente;
use App\Models\DetalleOrden;
use App\Models\Orden;
use App\Models\User;
use App\Support\ChatbotAccess;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InformesLaboratorio
{
    public const INFORMES = ['resumen', 'ordenes_por_estado', 'ingresos_por_dia', 'examenes_populares', 'ordenes_recientes', 'clientes_nuevos', 'ordenes_total', 'importe_total', 'pacientes_atendidos'];
    public const ESTADOS = ['todos', 'pendiente', 'en proceso', 'pausada', 'finalizado', 'cancelado'];

    public static function schema(): array
    {
        return [
            'type' => 'object', 'additionalProperties' => false,
            'properties' => [
                'informe' => ['type' => 'string', 'enum' => self::INFORMES],
                'desde' => ['type' => 'string', 'description' => 'Fecha inicial inclusiva YYYY-MM-DD.'],
                'hasta' => ['type' => 'string', 'description' => 'Fecha final inclusiva YYYY-MM-DD.'],
                'estado' => ['type' => 'string', 'enum' => self::ESTADOS],
                'limite' => ['type' => 'integer', 'minimum' => 1, 'maximum' => config('chatbot.max_rows')],
            ],
            'required' => ['informe'],
        ];
    }

    public function consultar(array $parametros, User $user): array
    {
        abort_unless(ChatbotAccess::allowed($user), 403);
        if (array_diff(array_keys($parametros), ['informe', 'desde', 'hasta', 'estado', 'limite'])) {
            throw ValidationException::withMessages(['informe' => 'Solo se aceptan los parámetros de los informes disponibles.']);
        }
        $p = Validator::make($parametros, [
            'informe' => ['required', Rule::in(self::INFORMES)],
            'desde' => ['sometimes', 'date_format:Y-m-d'],
            'hasta' => ['sometimes', 'date_format:Y-m-d'],
            'estado' => ['sometimes', Rule::in(self::ESTADOS)],
            'limite' => ['sometimes', 'integer', 'between:1,' . config('chatbot.max_rows')],
        ])->validate();
        $hoy = CarbonImmutable::now(config('chatbot.timezone'))->startOfDay();
        $desde = CarbonImmutable::parse($p['desde'] ?? $hoy->startOfMonth()->toDateString(), config('chatbot.timezone'))->startOfDay();
        $hasta = CarbonImmutable::parse($p['hasta'] ?? $hoy->toDateString(), config('chatbot.timezone'))->startOfDay();
        if ($hasta->lt($desde) || $desde->diffInDays($hasta) >= config('chatbot.max_days')) {
            throw ValidationException::withMessages(['desde' => 'El rango debe estar ordenado y abarcar hasta 366 días.']);
        }
        $fin = $hasta->addDay()->toDateString();
        $inicio = $desde->toDateString();
        $limite = (int) ($p['limite'] ?? config('chatbot.default_rows'));
        $estado = $p['estado'] ?? 'todos';
        if ($p['informe'] === 'clientes_nuevos' && $estado !== 'todos') {
            throw ValidationException::withMessages(['estado' => 'Las altas de clientes no se filtran por estado de orden. Puedes preguntar por pacientes con órdenes.']);
        }
        $ordenes = Orden::query()->where('fecha', '>=', $inicio)->where('fecha', '<', $fin);
        if ($estado !== 'todos') {
            $ordenes->where('estado', $estado);
        }
        $dinero = fn ($valor) => '$' . number_format((float) $valor, 2, '.', ',');
        $nota = 'Las fechas de órdenes corresponden a su fecha de registro. Importes en USD.';

        if (in_array($p['informe'], ['resumen', 'ingresos_por_dia', 'ordenes_recientes', 'importe_total'])) {
            abort_unless($user->can('ingresos_diarios'), 403);
        }
        $texto = '';
        $cantidadTotal = in_array($p['informe'], ['ordenes_total', 'ordenes_por_estado', 'ordenes_recientes']) ? (clone $ordenes)->count() : 0;
        switch ($p['informe']) {
            case 'ordenes_total':
                $titulo = 'Cantidad de órdenes';
                $columnas = ['indicador' => 'Indicador', 'valor' => 'Valor'];
                $filas = [['indicador' => 'Órdenes registradas', 'valor' => $cantidadTotal]];
                $texto = "Hay {$cantidadTotal} órdenes en el periodo consultado" . ($estado !== 'todos' ? " con estado {$estado}." : '.');
                break;
            case 'importe_total':
                $titulo = 'Importe total de órdenes';
                $vigentes = (clone $ordenes)->where('estado', '!=', 'cancelado');
                $cantidad = (clone $vigentes)->count();
                $importe = $dinero($vigentes->sum('total'));
                $columnas = ['cantidad' => 'Órdenes vigentes', 'importe' => 'Importe total'];
                $filas = [['cantidad' => $cantidad, 'importe' => $importe]];
                $texto = "Las {$cantidad} órdenes vigentes del periodo suman {$importe}.";
                $nota .= ' Excluye cancelaciones; no representa pagos comprobados ni utilidad.';
                break;
            case 'pacientes_atendidos':
                $titulo = 'Pacientes con órdenes';
                $cantidad = (clone $ordenes)->where('estado', '!=', 'cancelado')->distinct()->count('cliente_id');
                $columnas = ['indicador' => 'Indicador', 'valor' => 'Valor'];
                $filas = [['indicador' => 'Pacientes distintos con órdenes vigentes', 'valor' => $cantidad]];
                $texto = "Hay {$cantidad} pacientes distintos con órdenes vigentes en el periodo.";
                $nota = 'Cada cliente se cuenta una sola vez; excluye órdenes canceladas. No cuenta altas de clientes ni confirma atención médica.';
                break;
            case 'resumen':
                $totales = (clone $ordenes)->selectRaw('COUNT(*) as cantidad, SUM(CASE WHEN estado != ? THEN total ELSE 0 END) as importe', ['cancelado'])->first();
                $estados = (clone $ordenes)->selectRaw('estado, COUNT(*) as cantidad')->groupBy('estado')->pluck('cantidad', 'estado');
                $titulo = 'Resumen del laboratorio';
                $columnas = ['indicador' => 'Indicador', 'valor' => 'Valor'];
                $filas = [
                    ['indicador' => 'Órdenes registradas', 'valor' => (int) $totales->cantidad],
                    ['indicador' => 'Pendientes', 'valor' => (int) ($estados['pendiente'] ?? 0)],
                    ['indicador' => 'En proceso', 'valor' => (int) ($estados['en proceso'] ?? 0)],
                    ['indicador' => 'Finalizadas', 'valor' => (int) ($estados['finalizado'] ?? 0)],
                    ['indicador' => 'Importe de órdenes vigentes', 'valor' => $dinero($totales->importe)],
                ];
                $texto = 'Se registraron ' . (int) $totales->cantidad . ' órdenes. El importe vigente suma ' . $dinero($totales->importe) . '.';
                $nota .= ' El importe excluye órdenes canceladas; no representa pagos comprobados ni utilidad.';
                break;
            case 'ordenes_por_estado':
                $titulo = 'Órdenes por estado';
                $columnas = ['estado' => 'Estado', 'cantidad' => 'Órdenes'];
                $filas = (clone $ordenes)->selectRaw('estado, COUNT(*) as cantidad')->groupBy('estado')->orderBy('estado')
                    ->get()->map(fn ($r) => ['estado' => ucfirst($r->estado), 'cantidad' => (int) $r->cantidad])->all();
                $etiquetas = ['cancelado' => 'canceladas', 'finalizado' => 'finalizadas', 'pausada' => 'pausadas', 'pendiente' => 'pendientes', 'en proceso' => 'en proceso'];
                $texto = "Hay {$cantidadTotal} órdenes: " . (count($filas) ? implode(', ', array_map(fn ($r) => $r['cantidad'] . ' ' . ($etiquetas[mb_strtolower($r['estado'])] ?? mb_strtolower($r['estado'])), $filas)) . '.' : 'no se encontraron registros.');
                break;
            case 'ingresos_por_dia':
                $titulo = 'Importes de órdenes por día';
                $columnas = ['fecha' => 'Fecha', 'cantidad' => 'Órdenes', 'importe' => 'Importe vigente'];
                $filas = (clone $ordenes)->where('estado', '!=', 'cancelado')
                    ->selectRaw('DATE(fecha) as dia, COUNT(*) as cantidad, SUM(total) as importe')
                    ->groupByRaw('DATE(fecha)')->orderBy('dia')->get()
                    ->map(fn ($r) => ['fecha' => $r->dia, 'cantidad' => (int) $r->cantidad, 'importe' => $dinero($r->importe)])->all();
                $texto = 'El importe vigente del periodo suma ' . $dinero((clone $ordenes)->where('estado', '!=', 'cancelado')->sum('total')) . ', distribuido en ' . count($filas) . ' días con registros.';
                $nota .= ' Excluye cancelaciones. Son importes de órdenes, no pagos comprobados ni utilidad.';
                break;
            case 'examenes_populares':
                $titulo = 'Exámenes más solicitados';
                $columnas = ['examen' => 'Examen', 'cantidad' => 'Solicitudes'];
                $q = DetalleOrden::query()->join('ordens', 'detalle_orden.orden_id', '=', 'ordens.id')
                    ->where('ordens.fecha', '>=', $inicio)->where('ordens.fecha', '<', $fin)->where('ordens.estado', '!=', 'cancelado');
                if ($estado !== 'todos') $q->where('ordens.estado', $estado);
                $filas = $q->selectRaw('detalle_orden.nombre_examen as examen, COUNT(*) as cantidad')
                    ->groupBy('detalle_orden.nombre_examen')->orderByDesc('cantidad')->orderBy('examen')->limit($limite)
                    ->get()->map(fn ($r) => ['examen' => $r->examen ?: 'Sin nombre', 'cantidad' => (int) $r->cantidad])->all();
                $texto = count($filas) ? 'El examen más solicitado es ' . $filas[0]['examen'] . ', con ' . $filas[0]['cantidad'] . ' solicitudes.' : 'No hay exámenes solicitados en órdenes vigentes para este periodo.';
                $nota .= " Excluye órdenes canceladas. Muestra los primeros {$limite} exámenes por solicitudes.";
                break;
            case 'ordenes_recientes':
                $titulo = 'Órdenes recientes';
                $columnas = ['folio' => 'Orden', 'fecha' => 'Fecha', 'estado' => 'Estado', 'importe' => 'Importe'];
                $filas = (clone $ordenes)->orderByDesc('fecha')->orderByDesc('id')->limit($limite)
                    ->get(['id', 'fecha', 'estado', 'total'])->map(fn ($r) => [
                        'folio' => '#' . $r->id, 'fecha' => $r->fecha->format('Y-m-d'),
                        'estado' => ucfirst($r->estado), 'importe' => $dinero($r->total),
                    ])->all();
                $texto = 'Encontré ' . $cantidadTotal . ' órdenes; se muestran ' . count($filas) . ' de ellas, desde la más reciente.';
                $nota .= " Muestra hasta {$limite} órdenes. Incluye canceladas si no se filtra por estado.";
                break;
            case 'clientes_nuevos':
                $titulo = 'Clientes registrados por día';
                $columnas = ['fecha' => 'Fecha', 'cantidad' => 'Clientes nuevos'];
                $filas = Cliente::query()->where('created_at', '>=', $inicio)->where('created_at', '<', $fin)
                    ->selectRaw('DATE(created_at) as dia, COUNT(*) as cantidad')->groupByRaw('DATE(created_at)')->orderBy('dia')
                    ->get()->map(fn ($r) => ['fecha' => $r->dia, 'cantidad' => (int) $r->cantidad])->all();
                $texto = 'Se registraron ' . array_sum(array_column($filas, 'cantidad')) . ' clientes nuevos en este periodo.';
                $nota = 'Cuenta altas de clientes por fecha de creación; no cuenta visitas ni pacientes atendidos.';
                break;
        }
        $respuesta = [
            'informe' => $p['informe'], 'titulo' => $titulo, 'columnas' => $columnas, 'filas' => $filas,
            'periodo' => ['desde' => $inicio, 'hasta' => $hasta->toDateString(), 'estado' => $estado],
            'respuesta' => $texto, 'limite' => $limite, 'nota' => $nota, 'fuente' => 'Base de datos de ONCOSAVI',
            'consultado_en' => CarbonImmutable::now(config('chatbot.timezone'))->toIso8601String(),
        ];
        activity('Asistente')->causedBy($user)->event('consulted')
            ->withProperties(['informe' => $p['informe'], 'desde' => $inicio, 'hasta' => $hasta->toDateString(), 'estado' => $estado, 'filas' => count($filas)])
            ->log('Consulta de informe del laboratorio');

        return $respuesta;
    }
}
