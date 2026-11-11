<?php

namespace App\Http\Controllers\ControlCalidad;

use App\ControlCalidad\Services\ConsultaControlCalidad;
use App\ControlCalidad\Services\ExportadorControlCalidad;
use App\ControlCalidad\Services\PresentacionControlCalidad;
use App\Http\Controllers\Controller;
use App\Support\ControlCalidadPermissions as Cci;
use Illuminate\Http\Request;

class PdfController extends Controller
{
    public function presentacion(Request $request, ConsultaControlCalidad $consulta, PresentacionControlCalidad $presentacion)
    {
        if (! $request->user()) {
            return redirect()->guest(route('filament.admin.auth.login'));
        }
        $user = $request->user();
        abort_unless($user->can('access_admin_panel') && $user->can(Cci::VIEW) && $user->can(Cci::MAINTAIN) && $user->can(Cci::EXPORT), 403);
        $token = $request->query('acceso');
        abort_unless(is_string($token) && $consulta->tokenValido($token), 404);

        return $presentacion->generar(route('cci.supervision', ['token' => $token]))
            ->download('ONCOSAVI-Control-de-Calidad-Interno-Supervision.pdf')
            ->withHeaders(['Cache-Control' => 'no-store, private', 'Referrer-Policy' => 'no-referrer']);
    }

    public function exportar(Request $request, string $tipo, ExportadorControlCalidad $exportador)
    {
        if (! $request->user()) {
            return redirect()->guest(route('filament.admin.auth.login'));
        }
        $pdf = $exportador->generar($tipo, $request->query(), $request->user());

        return $pdf->download('control-calidad-'.$tipo.'-'.now()->format('Y-m-d').'.pdf')
            ->withHeaders(['Cache-Control' => 'no-store, private', 'X-Content-Type-Options' => 'nosniff']);
    }
}
