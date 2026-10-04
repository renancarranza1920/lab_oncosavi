<?php

namespace App\Filament\Pages;

use App\Models\Perfil;
use App\Models\Examen;
use App\Models\TipoExamen;
use App\Services\CatalogoExamenesPdf;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteExamenes extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationGroup = 'Reportes';
    protected static ?string $navigationLabel = 'Reporte de Exámenes';
    protected static ?int $navigationSort = 1;
    protected static ?string $title = 'Reporte de Exámenes y Perfiles';
    protected static string $view = 'filament.pages.reporte-examenes';

    public bool $mostrarPrecios = false;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('ver_catalogo_pdf') ?? false;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('descargarPdf')
                ->label('Descargar PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('primary')
                ->action(fn () => $this->descargarPdf()),
        ];
    }

    public function getResumenProperty(): array
    {
        $areas = TipoExamen::query()
            ->where('estado', 1)
            ->withCount(['examenes' => fn ($query) => $query->where('estado', 1)])
            ->get()
            ->where('examenes_count', '>', 0)
            ->sortByDesc('examenes_count')
            ->values();

        $examenes = Examen::query()->where('estado', 1);

        return [
            'areas' => $areas,
            'total_areas' => $areas->count(),
            'total_examenes' => (clone $examenes)->count(),
            'externos' => (clone $examenes)->where('es_externo', true)->count(),
            'perfiles' => Perfil::query()->where('estado', 1)->count(),
            'precio_promedio' => (float) ((clone $examenes)->avg('precio') ?? 0),
        ];
    }

    public function descargarPdf(): ?StreamedResponse
    {
        abort_unless(static::canAccess(), 403);
        $areas = TipoExamen::query()
            ->where('estado', 1)
            ->with(['examenes' => function ($query) {
                $query->where('estado', 1)
                    ->orderBy('nombre');
            }])
            ->orderBy('nombre')
            ->get()
            ->filter(fn (TipoExamen $area) => $area->examenes->isNotEmpty())
            ->values();

        $perfiles = Perfil::query()
            ->where('estado', 1)
            ->with(['examenes' => function ($query) {
                $query->where('estado', 1)
                    ->orderBy('nombre');
            }])
            ->orderBy('nombre')
            ->get();

        try {
            $pdf = app(CatalogoExamenesPdf::class)->generar($areas, $perfiles, $this->mostrarPrecios);
        } catch (ValidationException $exception) {
            Notification::make()
                ->title('El catálogo no cabe en dos páginas A4')
                ->body($exception->validator->errors()->first())
                ->danger()
                ->send();

            return null;
        }

        return response()->streamDownload(
            fn () => print($pdf->output()),
            'reporte-examenes-oncosavi-a4.pdf'
        );
    }
}
