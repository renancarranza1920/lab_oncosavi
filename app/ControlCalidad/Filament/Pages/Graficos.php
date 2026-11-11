<?php

namespace App\ControlCalidad\Filament\Pages;

use App\ControlCalidad\Filament\Widgets\GraficoNivel;
use App\ControlCalidad\Models\Area;
use App\ControlCalidad\Models\Nivel;
use App\ControlCalidad\Models\Prueba;
use App\Support\ControlCalidadPermissions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;

class Graficos extends PaginaControlCalidad
{
    protected static ?string $title = 'Gráficos';
    protected static ?string $slug = 'graficos';
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?int $navigationSort = 4;
    protected static string $view = 'filament.control-calidad.graficos';

    public ?array $data = [];

    #[Locked]
    public array $filtros = [];

    public static function permission(): string
    {
        return ControlCalidadPermissions::CHARTS;
    }

    public function mount(): void
    {
        $area = Area::query()->whereHas('pruebas.niveles')->orderBy('nombre')->first();
        $prueba = $area?->pruebas()->whereHas('niveles')->orderBy('nombre')->first();
        $this->form->fill([
            'area_id' => $area?->id,
            'prueba_id' => $prueba?->id,
            'desde' => now('America/El_Salvador')->subDays(30)->toDateString(),
            'hasta' => now('America/El_Salvador')->toDateString(),
        ]);
        if ($prueba !== null) {
            $this->aplicarFiltros();
        }
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Seleccionar controles')->schema([
                Grid::make(['default' => 1, 'md' => 2, 'xl' => 4])->schema([
                    Select::make('area_id')->label('Área y equipo')->required()
                        ->options(fn (): array => Area::query()->orderBy('nombre')->get()->mapWithKeys(fn (Area $area): array => [$area->id => $area->nombre.' · '.$area->equipo])->all())
                        ->searchable()->live()->afterStateUpdated(fn (Set $set) => $set('prueba_id', null))
                        ->rules([Rule::exists('cci_areas', 'id')]),
                    Select::make('prueba_id')->label('Prueba')->required()
                        ->options(fn (Get $get): array => Prueba::query()->where('area_id', $get('area_id'))->orderBy('nombre')->pluck('nombre', 'id')->all())
                        ->searchable()->disabled(fn (Get $get): bool => ! $get('area_id'))
                        ->rules(fn (Get $get): array => [Rule::exists('cci_pruebas', 'id')->where('area_id', $get('area_id'))]),
                    DatePicker::make('desde')->label('Desde')->required()->displayFormat('d/m/Y'),
                    DatePicker::make('hasta')->label('Hasta')->required()->displayFormat('d/m/Y')->afterOrEqual('desde'),
                ]),
            ]),
        ])->statePath('data');
    }

    public function aplicarFiltros(): void
    {
        abort_unless(static::canAccess(), 403);
        $this->filtros = $this->form->getState();
    }

    public function getNiveles(): Collection
    {
        abort_unless(static::canAccess(), 403);

        return isset($this->filtros['prueba_id'])
            ? Nivel::query()->where('prueba_id', $this->filtros['prueba_id'])->orderBy('orden')->orderBy('id')->get()
            : collect();
    }

    public function getChartWidgets(): array
    {
        return $this->getNiveles()->mapWithKeys(fn (Nivel $nivel): array => [
            $nivel->id.'-'.$this->filtros['desde'].'-'.$this->filtros['hasta'] => GraficoNivel::make([
                'nivelId' => (int) $nivel->id,
                'desde' => $this->filtros['desde'],
                'hasta' => $this->filtros['hasta'],
            ]),
        ])->all();
    }
}
