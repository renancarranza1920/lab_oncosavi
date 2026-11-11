<?php

namespace App\Providers\Filament;

use App\ControlCalidad\Filament\Pages\Graficos;
use App\ControlCalidad\Filament\Pages\Historial;
use App\ControlCalidad\Filament\Pages\Mantenimiento;
use App\ControlCalidad\Filament\Pages\Registro;
use App\ControlCalidad\Filament\Pages\Resumen;
use App\Http\Middleware\AuthenticateControlCalidad;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class ControlCalidadPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('control-calidad')
            ->path('control-calidad-interno')
            ->authGuard('web')
            ->brandName('Control de Calidad Interno')
            ->brandLogo(fn () => view('components.logo'))
            ->favicon(asset(config('laboratorio.logo')))
            ->colors(['primary' => config('ui.primary'), 'gray' => Color::Gray])
            ->topNavigation()
            ->pages([Resumen::class, Mantenimiento::class, Registro::class, Graficos::class, Historial::class])
            ->homeUrl(fn (): string => Resumen::getUrl(panel: 'control-calidad'))
            ->userMenuItems([
                MenuItem::make()->label('Volver a Oncosavi')->url('/admin')->icon('heroicon-o-arrow-left'),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([AuthenticateControlCalidad::class], isPersistent: true);
    }
}
