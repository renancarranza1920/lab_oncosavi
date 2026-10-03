<?php

namespace App\Http\Controllers\Expediente;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthMedicoController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $medico = Auth::guard('medico')->user();
        if ($medico?->portal_activo && $medico->password
            && $request->session()->get('medico_portal_version') === $medico->portal_version) {
            return redirect()->route('expediente.index');
        }

        return view('expediente.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'usuario' => ['required', 'string', 'max:32'],
            'password' => ['required', 'string', 'max:128'],
        ], [], ['usuario' => 'usuario', 'password' => 'contraseña']);
        $usuario = Str::upper(trim($data['usuario']));
        $key = 'portal-medico:'.hash('sha256', $usuario.'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['usuario' => 'Demasiados intentos. Intenta nuevamente en un minuto.']);
        }

        $id = preg_match('/^MED-([1-9][0-9]*)$/', $usuario, $matches) ? $matches[1] : 0;
        if (! Auth::guard('medico')->attempt(['id' => $id, 'password' => $data['password'], 'portal_activo' => true])) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['usuario' => 'El usuario o la contraseña no son correctos, o tu acceso no está habilitado.']);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $request->session()->put('medico_portal_version', Auth::guard('medico')->user()->portal_version);

        return redirect()->route('expediente.index');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('medico')->logout();
        $request->session()->forget('medico_portal_version');
        $request->session()->regenerate(true);
        $request->session()->regenerateToken();

        return redirect()->route('expediente.login');
    }
}
