<?php

namespace App\Http\Controllers;

use App\Services\Chatbot\InformesLaboratorio;
use App\Services\Chatbot\PlanificadorPreguntas;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ChatbotController extends Controller
{
    public function index()
    {
        $hoy = CarbonImmutable::now(config('chatbot.timezone'));

        return view('chatbot.index', ['desde' => $hoy->startOfMonth()->toDateString(), 'hasta' => $hoy->toDateString()]);
    }

    public function estado(PlanificadorPreguntas $planificador)
    {
        return response()->json($planificador->estado());
    }

    public function preguntar(Request $request, PlanificadorPreguntas $planificador, InformesLaboratorio $informes)
    {
        $p = $request->validate([
            'pregunta' => ['required', 'string', 'max:1000'],
            'desde' => ['required', 'date_format:Y-m-d'], 'hasta' => ['required', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'consulta_rapida' => ['nullable', Rule::in(InformesLaboratorio::INFORMES)],
            'contexto' => ['nullable', 'array:informe,desde,hasta,estado,limite'],
            'contexto.informe' => ['sometimes', Rule::in(InformesLaboratorio::INFORMES)],
            'contexto.desde' => ['sometimes', 'date_format:Y-m-d'],
            'contexto.hasta' => ['sometimes', 'date_format:Y-m-d'],
            'contexto.limite' => ['sometimes', 'integer', 'between:1,' . config('chatbot.max_rows')],
            'contexto.estado' => ['sometimes', Rule::in(InformesLaboratorio::ESTADOS)],
        ]);
        $periodo = ['desde' => $p['desde'], 'hasta' => $p['hasta']];
        $bloqueo = null;
        try {
            if ($p['consulta_rapida'] ?? null) {
                $parametros = array_merge($periodo, ['informe' => $p['consulta_rapida']]);
                $modo = 'guiado';
            } elseif ($rechazo = $planificador->rechazo($p['pregunta'])) {
                return response()->json(['message' => $rechazo], 422);
            } elseif ($parametros = $planificador->guiada($p['pregunta'], $periodo, $p['contexto'] ?? [])) {
                $modo = 'guiado';
            } else {
                // One model inference at a time across all owner sessions.
                $bloqueo = Cache::lock('chatbot-inferencia-local', config('chatbot.ai.timeout') + 20);
                if (!$bloqueo->get()) return response()->json(['message' => 'El asistente está atendiendo otra pregunta. Intenta de nuevo en un momento.'], 429);
                try {
                    $parametros = $planificador->interpretar($p['pregunta'], $periodo, $p['contexto'] ?? []);
                    $modo = 'ia_local';
                } catch (\RuntimeException $e) {
                    $parametros = $planificador->guiada($p['pregunta'], $periodo, $p['contexto'] ?? []);
                    if (!$parametros) return response()->json(['message' => $e->getMessage()], 503);
                    $modo = 'guiado';
                }
                if (($parametros['informe'] ?? null) === 'no_disponible') {
                    return response()->json(['message' => 'Puedo consultar resúmenes, cantidades y listas de órdenes, importes totales o por día, exámenes populares, clientes nuevos y pacientes atendidos. Prueba una de las consultas rápidas.'], 422);
                }
            }
            $resultado = $informes->consultar($parametros, $request->user());

            return response()->json(['modo' => $modo, 'resultado' => $resultado]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            throw $e;
        } catch (\Exception) {
            return response()->json(['message' => 'No fue posible consultar el informe. Intenta de nuevo.'], 503);
        } finally {
            $bloqueo?->release();
        }
    }
}
