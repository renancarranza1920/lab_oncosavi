<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Login;
use App\Filament\Pages\Auth\CustomLogin;
use App\Filament\Pages\DetalleOrdenKanban;
use Auth;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Carbon\Carbon;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use App\Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;

use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\RouteRegistrar; 
use Filament\Notifications\Notification;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        // Configura cada aspecto del panel de forma explícita, sin encadenar una línea tras otra.
        // Esto elimina las posibilidades de errores sutiles de encadenamiento.
        $panel->default();
        $panel->id('admin');
        $panel->path('admin');
        $panel->login( Login::class);
        $panel->passwordReset();
        $panel->revealablePasswords();
        $panel->profile(\App\Filament\Auth\EditProfile::class, isSimple: false);
        
        
        $panel->brandName(config('laboratorio.nombre'));
        $panel->colors([
            'primary' => config('ui.primary'),
            'gray' => Color::Gray,
            'info' => config('estados.info.base'),
            'success' => config('estados.success.base'),
            'warning' => config('estados.warning.base'),
            'danger' => config('estados.danger.base'),
        ]);
        $panel->renderHook('panels::head.end', fn () => view('partials.tema-oncosavi'));
        $panel->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources');
        $panel->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages');
        
        $panel->pages([
    	Dashboard::class,
    	DetalleOrdenKanban::class,
	]);
        $panel->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets');
        $panel->widgets([
            Widgets\AccountWidget::class,
          
        ]);
        $panel->middleware([
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

        ;
       


        $panel->plugins([
            FilamentShieldPlugin::make(),
        ]);
        $panel->authMiddleware([
            Authenticate::class,
        ]);
        $panel->brandLogo(fn () => view('components.logo'));
        $panel->favicon(asset(config('laboratorio.logo')));

        $panel->navigationGroups([
            'Atención al Paciente',    // Grupo 1 (Arriba)
            'Gestión de Laboratorio',  // Grupo 2
            'Reportes',
            'Catálogos y Ajustes',     // Grupo 3
            'Administración',          // Grupo 4 (Abajo)
        ]);
        
        
        // Finalmente, devuelve el objeto $panel configurado
        return $panel;
    }


}
