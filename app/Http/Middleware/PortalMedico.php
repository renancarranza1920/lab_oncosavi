<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PortalMedico
{
    public function handle(Request $request, Closure $next): Response
    {
        $medico = Auth::guard('medico')->user()?->fresh();
        if (! $medico || ! $medico->portal_activo || ! $medico->password
            || $request->session()->get('medico_portal_version') !== $medico->portal_version) {
            Auth::guard('medico')->logout();
            $request->session()->forget('medico_portal_version');

            return redirect()->route('expediente.login');
        }

        return $next($request);
    }
}
