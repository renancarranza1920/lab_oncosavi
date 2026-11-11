<?php

namespace App\Http\Controllers\ControlCalidad;

use App\ControlCalidad\Models\Area;
use App\ControlCalidad\Models\Prueba;
use App\ControlCalidad\Services\ConsultaControlCalidad;
use App\ControlCalidad\Services\DatosGraficoControlCalidad;
use App\ControlCalidad\Services\ExportadorControlCalidad;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SupervisionController extends Controller
{
    public function __invoke(Request $request, string $token, ConsultaControlCalidad $consulta)
    {
        abort_unless($consulta->tokenValido($token), 404);
        $valid = Validator::make($request->only(['desde', 'hasta', 'q', 'page', 'area_id', 'laboratorista_id', 'prueba_id']), [
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', ...($request->filled('desde') ? ['after_or_equal:desde'] : [])],
            'q' => ['nullable', 'string', 'max:180'],
            'page' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'area_id' => ['nullable', 'integer', 'exists:cci_areas,id'],
            'laboratorista_id' => ['nullable', 'integer', 'exists:users,id'],
            'prueba_id' => ['nullable', 'integer', 'exists:cci_pruebas,id'],
        ]);
        $errors = $valid->errors();
        $filtros = $valid->fails() ? [] : $valid->validated();
        $registros = $valid->fails() ? null : $consulta->registros($filtros, true)->paginate(10)->withQueryString();
        $areas = Area::orderBy('nombre')->get();
        $laboratoristas = User::whereIn('id', \App\ControlCalidad\Models\Registro::select('laboratorista_id'))->orderBy('name')->get(['id', 'name']);
        $pruebas = Prueba::whereHas('niveles.valorControlActual')->with('area')
            ->when($filtros['area_id'] ?? null, fn ($q, $id) => $q->where('area_id', $id))->orderBy('nombre')->get();
        $prueba = $pruebas->firstWhere('id', (int) ($filtros['prueba_id'] ?? 0)) ?? $pruebas->sortByDesc('id')->first();
        $graficos = [];
        $limiteGraficos = false;
        if ($prueba && ! $valid->fails()) {
            $query = $consulta->registros($filtros, true)->whereHas('resultados', fn ($q) => $q->where('prueba_id', $prueba->id));
            $limiteGraficos = $query->count() > 300;
            if (! $limiteGraficos) {
                $ids = $query->pluck('cci_registros.id')->all();
                $datos = app(DatosGraficoControlCalidad::class);
                foreach ($prueba->niveles()->orderBy('orden')->get() as $nivel) {
                    $chart = $datos->obtener($nivel->id, $filtros['desde'] ?? null, $filtros['hasta'] ?? null, $ids);
                    $graficos[] = ['id' => $nivel->id, 'nombre' => $nivel->nombre, 'data' => $chart, 'options' => $datos->opciones($chart)];
                }
            }
        }
        $exportUrl = route('cci.supervision.pdf', ['token' => $token]);

        return response()->view('control-calidad.supervision', compact('registros', 'filtros', 'errors', 'areas', 'laboratoristas', 'pruebas', 'prueba', 'graficos', 'limiteGraficos', 'exportUrl'), $valid->fails() ? 422 : 200)
            ->withHeaders($this->headers());
    }

    public function pdf(Request $request, string $token, ConsultaControlCalidad $consulta, ExportadorControlCalidad $exportador)
    {
        abort_unless($consulta->tokenValido($token), 404);
        $params = $request->only(['desde', 'hasta', 'q', 'area_id', 'laboratorista_id', 'prueba_id', 'ids', 'incluir_graficos']);
        $params['incluir_graficos'] ??= 1;

        return $exportador->generarSupervision($params, $token)->download('oncosavi-supervision-control-calidad.pdf')
            ->withHeaders($this->headers());
    }

    private function headers(): array
    {
        return [
            'Cache-Control' => 'no-store, private', 'Referrer-Policy' => 'no-referrer',
            'X-Robots-Tag' => 'noindex, nofollow, noarchive', 'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
        ];
    }
}
