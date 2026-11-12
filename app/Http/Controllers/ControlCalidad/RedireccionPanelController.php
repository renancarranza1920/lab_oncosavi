<?php

namespace App\Http\Controllers\ControlCalidad;

use App\ControlCalidad\Filament\Pages\Graficos;
use App\ControlCalidad\Filament\Pages\Historial;
use App\ControlCalidad\Filament\Pages\Mantenimiento;
use App\ControlCalidad\Filament\Pages\Registro;
use App\ControlCalidad\Filament\Pages\Resumen;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RedireccionPanelController
{
    public function __invoke(Request $request, string $pantalla = ''): RedirectResponse
    {
        // Conserva los enlaces anteriores y sus filtros dentro del panel general.
        $page = match ($pantalla) {
            'mantenimiento' => Mantenimiento::class,
            'registro' => Registro::class,
            'graficos' => Graficos::class,
            'historial' => Historial::class,
            default => Resumen::class,
        };

        return redirect()->to($page::getUrl(parameters: $request->query(), panel: 'admin'));
    }
}
