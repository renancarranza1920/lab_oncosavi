<?php

namespace App\ControlCalidad\Filament\Widgets;

use App\ControlCalidad\Filament\Pages\PaginaControlCalidad;
use App\ControlCalidad\Models\Nivel;
use App\ControlCalidad\Models\Resultado;
use App\Support\ControlCalidadPermissions;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Locked;

class GraficoNivel extends ChartWidget
{
    #[Locked]
    public int $nivelId = 0;

    #[Locked]
    public ?string $desde = null;

    #[Locked]
    public ?string $hasta = null;

    protected static bool $isLazy = false;

    protected static ?string $pollingInterval = null;

    protected static ?string $maxHeight = '300px';

    protected static string $view = 'filament.control-calidad.widgets.grafico-nivel';

    public static function canView(): bool
    {
        return PaginaControlCalidad::allowed(ControlCalidadPermissions::CHARTS);
    }

    public function mount(): void
    {
        abort_unless(static::canView(), 403);
        Validator::make(['desde' => $this->desde, 'hasta' => $this->hasta], [
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', ...($this->desde ? ['after_or_equal:desde'] : [])],
        ])->validate();
        parent::mount();
    }

    protected function getType(): string
    {
        return 'line';
    }

    public function getHeading(): string
    {
        abort_unless(static::canView(), 403);

        return Nivel::findOrFail($this->nivelId)->nombre;
    }

    public function getDescription(): string
    {
        abort_unless(static::canView(), 403);
        $nivel = Nivel::with('prueba')->findOrFail($this->nivelId);
        $unidades = collect($this->getCachedData()['datasets'])->pluck('unidad')->unique()->implode(', ');

        return $nivel->prueba->nombre.($unidades !== '' ? ' · '.$unidades : '');
    }

    protected function getData(): array
    {
        abort_unless(static::canView(), 403);
        $query = Resultado::query()
            ->join('cci_registros as registro', 'registro.id', '=', 'cci_resultados.registro_id')
            ->whereNull('registro.deleted_at')
            ->where('cci_resultados.nivel_id', $this->nivelId)
            ->orderBy('registro.fecha_hora')
            ->orderBy('cci_resultados.id')
            ->select('cci_resultados.*', 'registro.fecha_hora');
        if ($this->desde) {
            $query->where('registro.fecha_hora', '>=', CarbonImmutable::parse($this->desde, 'America/El_Salvador')->startOfDay());
        }
        if ($this->hasta) {
            $query->where('registro.fecha_hora', '<=', CarbonImmutable::parse($this->hasta, 'America/El_Salvador')->endOfDay());
        }
        $lecturas = $query->get();
        $labels = $lecturas->map(fn (Resultado $resultado): string => CarbonImmutable::parse($resultado->fecha_hora, 'America/El_Salvador')
            ->format('d/m/Y H:i'))->all();
        $groups = [];
        foreach ($lecturas as $index => $lectura) {
            $key = json_encode([$lectura->lote_nombre, $lectura->lote_numero, $lectura->unidad, $lectura->esperado, $lectura->minimo, $lectura->maximo]);
            $groups[$key]['indices'][] = $index;
            $groups[$key]['lote'] = $lectura->lote_numero;
            $groups[$key]['unidad'] = $lectura->unidad;
        }
        $datasets = [];
        $axes = [];
        foreach ($groups as $group) {
            $label = 'Lote '.$group['lote'].' · '.$group['unidad'];
            $axes[$group['unidad']] ??= $axes === [] ? 'y' : 'unidad-'.substr(hash('sha256', $group['unidad']), 0, 8);
            $maximoIndex = count($datasets) + 3;
            foreach (['valor' => ['Medido', '#2563eb'], 'esperado' => ['Esperado', '#0f766e'], 'minimo' => ['Mínimo', '#64748b'], 'maximo' => ['Máximo', '#64748b']] as $field => [$name, $color]) {
                $values = array_fill(0, $lecturas->count(), null);
                $pointColors = [];
                $pointStyles = [];
                foreach ($group['indices'] as $index) {
                    $reading = $lecturas[$index];
                    $values[$index] = (float) $reading->{$field};
                    $outside = (float) $reading->valor < (float) $reading->minimo || (float) $reading->valor > (float) $reading->maximo;
                    $pointColors[$index] = $outside ? '#dc2626' : $color;
                    $pointStyles[$index] = $outside ? 'crossRot' : 'circle';
                }
                $datasets[] = [
                    'label' => $name.' · '.$label,
                    'data' => $values,
                    'borderColor' => $color,
                    'backgroundColor' => $field === 'minimo' ? 'rgba(20, 184, 166, 0.16)' : $color,
                    'fill' => $field === 'minimo' ? ['target' => $maximoIndex] : false,
                    'borderWidth' => $field === 'valor' ? 2 : 1,
                    'borderDash' => $field === 'valor' ? [] : [5, 4],
                    'pointRadius' => $field === 'valor' ? 4 : 0,
                    'pointHoverRadius' => $field === 'valor' ? 6 : 0,
                    'pointBackgroundColor' => $field === 'valor' ? array_replace(array_fill(0, $lecturas->count(), $color), $pointColors) : $color,
                    'pointBorderColor' => $field === 'valor' ? array_replace(array_fill(0, $lecturas->count(), $color), $pointColors) : $color,
                    'pointBorderWidth' => $field === 'valor' ? 2 : 1,
                    'pointStyle' => $field === 'valor' ? array_replace(array_fill(0, $lecturas->count(), 'circle'), $pointStyles) : 'circle',
                    'spanGaps' => false,
                    'tension' => 0,
                    'yAxisID' => $axes[$group['unidad']],
                    'unidad' => $group['unidad'],
                ];
            }
        }

        return ['datasets' => $datasets, 'labels' => $labels];
    }

    protected function getOptions(): array
    {
        $scales = ['x' => ['ticks' => ['maxTicksLimit' => 6]]];
        foreach ($this->getCachedData()['datasets'] as $dataset) {
            if (isset($scales[$dataset['yAxisID']])) {
                continue;
            }
            $scales[$dataset['yAxisID']] = [
                'type' => 'linear',
                'position' => count($scales) % 2 ? 'left' : 'right',
                'beginAtZero' => false,
                'title' => ['display' => true, 'text' => $dataset['unidad']],
                'grid' => ['drawOnChartArea' => count($scales) === 1],
            ];
        }

        return [
            'maintainAspectRatio' => false,
            'interaction' => ['mode' => 'index', 'intersect' => false],
            'plugins' => ['legend' => ['position' => 'bottom', 'labels' => ['boxWidth' => 12]]],
            'scales' => $scales,
        ];
    }
}
