<?php

namespace App\Filament\Resources\OrdenResource\Pages;

use App\Filament\Pages\DetalleOrdenKanban;
use App\Models\Orden;
use Filament\Resources\Pages\Page;

class OrdenKanbanView extends Page
{
    public Orden $orden;

    protected static string $view = 'filament.pages.detalle-orden-kanban';
    protected static ?string $title = 'Kanban de Orden';
    protected static bool $shouldRegisterNavigation = false;

    public function mount(Orden $orden)
    {
        $this->orden = $orden;
    }
}
