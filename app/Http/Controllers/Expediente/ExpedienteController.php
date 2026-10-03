<?php

namespace App\Http\Controllers\Expediente;

use App\Http\Controllers\Controller;
use App\Models\Medico;
use App\Services\ExpedienteMedicoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExpedienteController extends Controller
{
    public function __construct(private readonly ExpedienteMedicoService $expedientes) {}

    private function medico(): Medico
    {
        return Auth::guard('medico')->user();
    }

    public function index(Request $request): View
    {
        $filtros = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'fecha_nacimiento' => ['nullable', 'date_format:Y-m-d'],
            'genero' => ['nullable', Rule::in(['Masculino', 'Femenino'])],
            'estado' => ['nullable', Rule::in(['Activo', 'Inactivo'])],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', Rule::when($request->filled('desde'), 'after_or_equal:desde')],
            'hora_desde' => ['nullable', 'date_format:H:i'],
            'hora_hasta' => ['nullable', 'date_format:H:i', Rule::when($request->filled('hora_desde'), 'after_or_equal:hora_desde')],
        ], [
            'hasta.after_or_equal' => 'La fecha final debe ser igual o posterior a la inicial.',
            'hora_hasta.after_or_equal' => 'La hora final debe ser igual o posterior a la inicial.',
        ]);

        return view('expediente.index', [
            'medico' => $this->medico(),
            'filtros' => $filtros,
            'pacientes' => $this->expedientes->buscar($this->medico(), $filtros)->paginate(18)->withQueryString(),
        ]);
    }

    public function show(Request $request, int $paciente): View
    {
        $cliente = $this->expedientes->pacientes($this->medico())->findOrFail($paciente);
        $ordenes = $this->expedientes->ordenes($this->medico())
            ->where('cliente_id', $cliente->id)
            ->with(['detalleOrden', 'medico'])
            ->orderByDesc('fecha')->orderByDesc('id')
            ->paginate(12)->withQueryString();

        return view('expediente.show', ['medico' => $this->medico(), 'cliente' => $cliente, 'ordenes' => $ordenes]);
    }

    public function pdf(Request $request, int $orden): BinaryFileResponse
    {
        $registro = $this->expedientes->ordenes($this->medico())->with('cliente')->findOrFail($orden);
        abort_unless($registro->estado === 'finalizado' && $registro->reporteGuardadoExists(), 404);

        // Se devuelve el archivo final original. No se recalculan resultados ni firmas.
        return response()->file($registro->reporteGuardadoFullPath(), ['Content-Type' => 'application/pdf'])
            ->setContentDisposition($request->boolean('descargar') ? 'attachment' : 'inline', $registro->reporteGuardadoFileName());
    }
}
