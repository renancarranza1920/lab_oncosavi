<?php

namespace App\Filament\Resources\PerfilResource\Pages;

use App\Filament\Resources\PerfilResource;
use App\Models\Examen;
use App\Models\TipoExamen;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListPerfils extends ListRecords
{
    protected static string $resource = PerfilResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
 public function getTabs(): array
    {
        return [
            'todos' => Tab::make('Todos'),

            'simples' => Tab::make('Pequeños (1-3 Exámenes)')
                ->modifyQueryUsing(fn ($query) => $query->has('examenes', '<=', 3)),

            'complejos' => Tab::make('Completos (+4 Exámenes)')
                ->modifyQueryUsing(fn ($query) => $query->has('examenes', '>', 3)),
        ];
    }
}
