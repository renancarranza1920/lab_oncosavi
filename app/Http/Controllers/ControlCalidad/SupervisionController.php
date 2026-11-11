<?php

namespace App\Http\Controllers\ControlCalidad;

use App\ControlCalidad\Services\ConsultaControlCalidad;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SupervisionController extends Controller
{
    public function __invoke(Request $request, string $token, ConsultaControlCalidad $consulta)
    {
        abort_unless($consulta->tokenValido($token), 404);
        $valid = Validator::make($request->only(['desde', 'hasta', 'q', 'page']), [
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', ...($request->filled('desde') ? ['after_or_equal:desde'] : [])],
            'q' => ['nullable', 'string', 'max:180'],
            'page' => ['nullable', 'integer', 'min:1', 'max:1000000'],
        ]);
        $errors = $valid->errors();
        $filtros = $valid->fails() ? [] : $valid->validated();
        $registros = $valid->fails() ? null : $consulta->registros($filtros, true)->paginate(10)->withQueryString();

        return response()->view('control-calidad.supervision', compact('registros', 'filtros', 'errors'), $valid->fails() ? 422 : 200)
            ->withHeaders([
                'Cache-Control' => 'no-store, private', 'Referrer-Policy' => 'no-referrer',
                'X-Robots-Tag' => 'noindex, nofollow, noarchive', 'X-Content-Type-Options' => 'nosniff',
                'X-Frame-Options' => 'DENY',
            ]);
    }
}
