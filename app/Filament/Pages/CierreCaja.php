<?php

namespace App\Filament\Pages;

use App\Models\Orden;
use App\Services\CierreCajaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CierreCaja extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Reportes';
    protected static ?string $navigationLabel = 'Reporte Financiero';
    protected static ?int $navigationSort = 2;
    protected static string $view = 'filament.pages.cierre-caja';

    public string $periodo = 'mensual';
    public int $mes;
    public int $trimestre;
    public int $anio;
    public string $fecha;

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $hoy = now();
        $this->mes = (int) $hoy->month;
        $this->trimestre = (int) ceil($hoy->month / 3);
        $this->anio = (int) $hoy->year;
        $this->fecha = $hoy->toDateString();
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view_reports') ?? false;
    }

    public function getTitle(): string
    {
        return 'Reporte Financiero - ' . $this->datos['etiqueta'];
    }

    public function getSubheading(): ?string
    {
        $datos = $this->datos;

        return 'Del ' . $datos['desde']->format('d/m/Y') . ' al ' . $datos['hasta']->format('d/m/Y');
    }

    public function getDatosProperty(): array
    {
        $fecha = Carbon::parse($this->fecha);

        return app(CierreCajaService::class)->generar(
            $this->periodo,
            $this->periodo === 'diario' ? (int) $fecha->year : $this->anio,
            $this->periodo === 'diario' ? (int) $fecha->month : $this->mes,
            $this->trimestre,
            (int) $fecha->day,
        );
    }

    public function getAniosProperty(): array
    {
        $primeraFecha = Orden::query()->min('fecha');
        $primero = (int) ($primeraFecha ? Carbon::parse($primeraFecha)->year : now()->year);
        $actual = (int) now()->year;

        return range(max(2000, $primero), max($actual, $primero));
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('descargarPdf')
                ->label('Descargar PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('primary')
                ->action(fn (): StreamedResponse => $this->descargarPdf()),
        ];
    }

    public function descargarPdf(): StreamedResponse
    {
        abort_unless(static::canAccess(), 403);

        $datos = $this->datos;
        $pdf = Pdf::setOptions([
            'isRemoteEnabled' => false,
            'isHtml5ParserEnabled' => true,
            'dpi' => 96,
            'chroot' => base_path(),
        ])->loadView('pdf.cierre-caja', $datos + [
            'logoPath' => public_path(config('laboratorio.logo')),
            'generadoPor' => auth()->user()?->name ?? config('laboratorio.nombre'),
        ])->setPaper('letter', 'landscape');

        $sufijo = match ($this->periodo) {
            'diario' => Carbon::parse($this->fecha)->format('Y-m-d'),
            'anual' => (string) $this->anio,
            'trimestral' => "{$this->anio}-T{$this->trimestre}",
            default => sprintf('%d-%02d', $this->anio, $this->mes),
        };

        return response()->streamDownload(
            fn () => print($pdf->output()),
            "cierre-caja-{$this->periodo}-{$sufijo}.pdf",
        );
    }
}
