<?php

namespace App\ControlCalidad\Filament\Pages;

use App\Support\ControlCalidadPermissions;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Pages\Page;

abstract class PaginaControlCalidad extends Page
{
    abstract public static function permission(): string;

    public static function allowed(string $permission): bool
    {
        $user = auth('web')->user();

        return $user !== null
            && $user->can('access_admin_panel')
            && $user->can(ControlCalidadPermissions::VIEW)
            && $user->can($permission);
    }

    public static function canAccess(): bool
    {
        return static::allowed(static::permission());
    }

    public function hydrate(): void
    {
        abort_unless(static::canAccess(), 403);
    }

    protected function getHeaderActions(): array
    {
        if (! static::allowed(ControlCalidadPermissions::EXPORT)) {
            return [];
        }
        if ($this instanceof Registro && ! $this->mostrarRegistros()) {
            return [];
        }
        $tipo = match (static::class) {
            Resumen::class => 'resumen', Mantenimiento::class => 'catalogo', Registro::class => 'registros',
            Historial::class => 'historial', Graficos::class => 'graficos', default => null,
        };
        if ($tipo === null) {
            return [];
        }
        $url = fn (bool $graficos) => route('cci.exportar', ['tipo' => $tipo, ...$this->parametrosExportacion(), 'incluir_graficos' => (int) $graficos]);
        if (in_array($tipo, ['catalogo', 'graficos'], true)) {
            return [Action::make('exportar_pdf')->label('Exportar PDF')->icon('heroicon-o-arrow-down-tray')
                ->url(fn () => $url($tipo === 'graficos'))->openUrlInNewTab()];
        }

        return [ActionGroup::make([
            Action::make('exportar_datos')->label('Solo datos')->url(fn () => $url(false))->openUrlInNewTab(),
            Action::make('exportar_datos_graficos')->label('Datos y gráficos')
                ->visible(fn () => static::allowed(ControlCalidadPermissions::CHARTS))->url(fn () => $url(true))->openUrlInNewTab(),
        ])->label('Exportar PDF')->icon('heroicon-o-arrow-down-tray')->button()];
    }

    public function parametrosExportacion(): array
    {
        if ($this instanceof Mantenimiento) {
            return ['seccion' => $this->seccion];
        }
        if ($this instanceof Graficos) {
            return $this->filtros;
        }
        if ($this instanceof Historial || $this instanceof Registro) {
            $filters = $this->tableFilters ?? [];

            return array_filter([
                'desde' => $filters['fecha_hora']['desde'] ?? null, 'hasta' => $filters['fecha_hora']['hasta'] ?? null,
                'area_id' => $filters['area_id']['value'] ?? null, 'laboratorista_id' => $filters['laboratorista_id']['value'] ?? null,
                'q' => $this->getTableSearch(),
            ], fn ($value) => $value !== null && $value !== '');
        }

        return [];
    }
}
