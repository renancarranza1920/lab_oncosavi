<?php

namespace App\Filament\Resources\OrdenResource\Pages;

use App\Filament\Resources\OrdenResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListOrdens extends ListRecords
{
    protected static string $resource = OrdenResource::class;
    public ?string $activeTab = 'pendiente';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
protected $listeners = [
        'montarAccionWhatsapp' => 'abrirModalWhatsapp'
    ];

    public function abrirModalWhatsapp($data)
    {
        // Esto busca la acción 'enviarWhatsapp' definida en la tabla y la ejecuta
        // sobre el registro específico.
        $this->mountTableAction('enviarWhatsapp', \App\Models\Orden::find($data['recordId']));
    }
    public function getTabs(): array
    {
        $count = OrdenResource::getEloquentQuery()
         ->selectRaw('COUNT(*) as total')
         ->selectRaw('SUM(CASE WHEN estado = "pendiente" THEN 1 ELSE 0 END) as pending')
            ->selectRaw('SUM(CASE WHEN estado = "en proceso" THEN 1 ELSE 0 END) as en_proceso')
            ->selectRaw('SUM(CASE WHEN estado = "pausada" THEN 1 ELSE 0 END) as pausada')
            ->selectRaw('SUM(CASE WHEN estado = "finalizado" THEN 1 ELSE 0 END) as finalizado')
            ->selectRaw('SUM(CASE WHEN estado = "cancelado" THEN 1 ELSE 0 END) as cancelado')
         ->first();
        return [
            'todas' => Tab::make('Todas')
            ->badgeColor('gray')
           ->badgeIcon('heroicon-o-folder')
            ->badge(fn () => $count->total),


            'pendiente' => Tab::make('Pendientes')
            ->badgeColor('warning')
            ->badgeIcon('heroicon-o-clock')
            ->badge(fn () => $count->pending)
            ->modifyQueryUsing(fn ($query) => $query->where('estado', 'pendiente')),

            'en proceso' => Tab::make('En proceso')
                ->badgeColor('info')
                ->badgeIcon('heroicon-o-cog')
               ->badge(fn () => $count->en_proceso)
               ->modifyQueryUsing(fn ($query) => $query->where('estado', 'en proceso')),
            'pausada' => Tab::make('Pausadas')
                ->badgeColor('danger')
                ->badgeIcon('heroicon-o-pause-circle')
                ->badge(fn () => $count->pausada)
                ->modifyQueryUsing(fn ($query) => $query->where('estado', 'pausada')),

            'finalizado' => Tab::make('Finalizadas')
                ->badgeColor('success')
                ->badgeIcon('heroicon-o-check')
                ->badge(fn () => $count->finalizado)
                ->modifyQueryUsing(fn ($query) => $query->where('estado', 'finalizado')),

            'cancelado' => Tab::make('Canceladas')
                ->badgeColor('danger')
                ->badgeIcon('heroicon-o-x-circle')
                ->badge(fn () => $count->cancelado)
                ->modifyQueryUsing(fn ($query) => $query->where('estado', 'cancelado')),
        ];
    }

    protected function getTotalCount(): int
    {
        return $this->getTableQuery()->count();
    }

    protected function getPendingCount(): int
    {
        return $this->getTableQuery()->where('estado', 'pendiente')->count();
    }
}
