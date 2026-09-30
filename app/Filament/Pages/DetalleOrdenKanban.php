<?php

namespace App\Filament\Pages;

use App\Enums\RecipienteEnum;
use App\Models\DetalleOrden;
use App\Models\Orden;
use App\Services\ZebraLabelService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Mokhosh\FilamentKanban\Pages\KanbanBoard;
use Illuminate\Support\Facades\Auth;

class DetalleOrdenKanban extends KanbanBoard
{
    #[\Livewire\Attributes\Url]
    public ?int $ordenId = null;

    protected static ?string $navigationLabel = 'Kanban Etiquetas';
    protected static ?string $title = 'Etiquetas de Exámenes';
    protected static string $model = DetalleOrden::class;
    protected static string $recordTitleAttribute = 'nombre_examen';

    protected static string $statusEnum = \App\Enums\RecipienteEnum::class;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public array $extraRecipientes = [];

    protected function getBoardStyles(): string
    {
        return 'w-full';
    }

    protected function getColumnWidth(): string
    {
        return 'w-full';
    }

    protected function getGridColumns(): string
    {
        return 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4';
    }

    protected function statuses(): Collection
    {
        return RecipienteEnum::statuses();
    }

    protected function records(): Collection
    {
        if (!$this->ordenId) {
            return collect();
        }

        return DetalleOrden::where('orden_id', $this->ordenId)
            ->ordered()
            ->get();
    }

    protected function getRecordTitle(): string
    {
        return $this->recordTitleAttribute;
    }

    public function onStatusChanged(int|string $recordId, string $status, array $fromOrderedIds, array $toOrderedIds): void
    {
        if (!auth()->user()->can('mover_etiquetas_kanban')) {
            Notification::make()
                ->title('No tienes permiso para mover etiquetas.')
                ->danger()
                ->send();
            return;
        }

        $detalle = DetalleOrden::find($recordId);
        if ($detalle) {
            $detalle->update(['status' => $status]);
        }

        DetalleOrden::setNewOrder($toOrderedIds);

        // Registrar bitácora
        if ($detalle && $detalle->orden) {
            activity()
                ->causedBy(Auth::user())
                ->performedOn($detalle->orden)
                ->log("Movió la etiqueta '{$detalle->nombre_examen}' al estado '{$status}' en la Orden #{$this->ordenId}");
        }
    }

    public function onSortChanged(int|string $recordId, string $status, array $orderedIds): void
    {
        DetalleOrden::setNewOrder($orderedIds);
    }

    public function mount(): void
    {
        parent::mount();

        if ($this->ordenId) {
            $orden = Orden::find($this->ordenId);
            if ($orden) {
                static::$title = 'Etiquetas de Exámenes - Orden #' . $orden->id;

                if (session()->get('from_create_orden')) {
                    Notification::make()
                        ->title('Orden creada con éxito')
                        ->body('ID de la orden: ' . $orden->id)
                        ->success()
                        ->duration(1000)
                        ->send();
                    session()->forget('from_create_orden');
                }
            }
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('Generar ZPL')
                ->label('Generar Etiquetas ZPL')
                ->icon('heroicon-o-printer')
                ->visible(fn() => auth()->user()->can('imprimir_etiquetas_kanban'))
                ->color('warning')
                ->action(fn() => $this->printAll()),

            Action::make('Volver a Ordenes')
    ->label('Volver a Ordenes')
    // Inyectamos el parámetro de búsqueda en la URL
    ->url(route('filament.admin.resources.ordenes.index', [
        'tableSearch' => $this->ordenId // <--- Esto pre-carga la búsqueda
    ]))
    ->icon('heroicon-o-arrow-left') // Opcional: Un icono para volver
    ->color('gray') // Opcional: Color neutral
    ->openUrlInNewTab(false),
        ];
    }

    // ---------------------------------------------------------------------
    // IMPRESIONES CON QZ-TRAY (FRONTEND)
    // ---------------------------------------------------------------------

    public function printGroup(string $status): void
    {
        if (!auth()->user()->can('imprimir_etiquetas_kanban')) {
            Notification::make()->title('Acceso denegado')->danger()->send();
            return;
        }

        if (!$this->ordenId) {
            return;
        }

        $detalles = DetalleOrden::with('orden.cliente')
            ->where('orden_id', $this->ordenId)
            ->where('status', $status)
            ->get();

        if ($detalles->isEmpty()) {
            Notification::make()->title('No hay detalles')->warning()->send();
            return;
        }

        $service = new ZebraLabelService();
        $zpl = $service->generarZplMultiple($detalles);

        // 🔥 Enviar ZPL al navegador
        $this->dispatch('imprimirZplFrontend', zpl: $zpl);

        Notification::make()->title('Etiquetas enviadas para impresión')->success()->send();
    }

    public function printAll(): void
    {
        if (!auth()->user()->can('imprimir_etiquetas_kanban')) {
            Notification::make()->title('Acceso denegado')->danger()->send();
            return;
        }

        $detalles = DetalleOrden::with('orden.cliente')
            ->where('orden_id', $this->ordenId)
            ->get();

        if ($detalles->isEmpty()) {
            Notification::make()->title('No hay etiquetas')->warning()->send();
            return;
        }

        $service = new ZebraLabelService();
        $zpl = $service->generarZplMultiple($detalles);

        // 🔥 Enviar ZPL al navegador
        $this->dispatch('imprimirZplFrontend', zpl: $zpl);

        Notification::make()->title('Etiquetas enviadas para impresión')->success()->send();
    }

    public function printSingle(int $recordId): void
    {
        if (!auth()->user()->can('imprimir_etiquetas_kanban')) {
            Notification::make()->title('Acceso denegado')->danger()->send();
            return;
        }

        $detalle = DetalleOrden::with('orden.cliente')->find($recordId);

        if (!$detalle) {
            Notification::make()->title('Detalle no encontrado')->danger()->send();
            return;
        }

        $service = new ZebraLabelService();
        $zpl = $service->generarZpl($detalle);

        // 🔥 Enviar ZPL al navegador
        $this->dispatch('imprimirZplFrontend', zpl: $zpl);

        Notification::make()->title('Etiqueta enviada para impresión')->success()->send();
    }
}
