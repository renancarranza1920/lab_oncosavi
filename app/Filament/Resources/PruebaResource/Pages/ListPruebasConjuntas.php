<?php

namespace App\Filament\Resources\PruebaResource\Pages;

use App\Filament\Resources\PruebaResource;
use App\Models\Prueba;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;

class ListPruebasConjuntas extends Page implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    protected static string $resource = PruebaResource::class;
    protected static string $view = 'filament.resources.prueba-resource.pages.list-pruebas-conjuntas';

    #[Url(keep: true)]
    public string $search = '';

    public array $matrices = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('ver_pruebas_conjuntas'), 403);
        $this->reconstruirMatrices();
    }

    public function updatedSearch(): void
    {
        $this->reconstruirMatrices();
    }

    protected function reconstruirMatrices(): void
    {
        $query = Prueba::whereNotNull('tipo_conjunto')->with('examen');

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('nombre', 'like', '%' . $this->search . '%')
                    ->orWhere('tipo_conjunto', 'like', '%' . $this->search . '%')
                    ->orWhereHas('examen', function ($examenQuery) {
                        $examenQuery->where('nombre', 'like', '%' . $this->search . '%');
                    });
            });
        }

        $pruebasAgrupadas = $query->get()->groupBy('tipo_conjunto');

        $this->matrices = $pruebasAgrupadas->map(function ($pruebas, $tipoConjunto) {
            $filas = [];
            $columnas = [];
            $dataMatrix = [];

            foreach ($pruebas as $prueba) {
                $partes = explode(', ', $prueba->nombre);
                if (count($partes) >= 2) {
                    $nombreFila = $partes[0];
                    $nombreColumna = $partes[1];
                    
                    $filas[] = $nombreFila;
                    $columnas[] = $nombreColumna;
                    
                    $dataMatrix[$nombreFila][$nombreColumna] = $prueba;
                }
            }

            return [
                'tipo_conjunto' => $tipoConjunto,
                'examen' => $pruebas->first()->examen->nombre ?? 'N/A',
                'filas' => array_values(array_unique($filas)),
                'columnas' => array_values(array_unique($columnas)),
                'data' => $dataMatrix,
            ];
        })->values()->all();
    }

   public function deleteConjuntoAction(): ?Action
{
    if (! auth()->user()->can('eliminar_pruebas_conjuntas')) {
        return null;
    }

    return Action::make('deleteConjunto')
        ->label('Eliminar Conjunto')
        ->requiresConfirmation()
        ->modalHeading('Eliminar conjunto de pruebas')
        ->modalDescription(
            '¿Estás seguro de que deseas eliminar todas las pruebas de este conjunto? Esta acción no se puede deshacer.'
        )
        ->color('danger')
        ->extraAttributes([
            'style' => 'margin-left: 8px;',
        ])
        ->action(function (Action $action) {
            $arguments = $action->getArguments();
            $tipoConjunto = $arguments['tipo_conjunto'] ?? null;

            if (! $tipoConjunto) {
                return;
            }

            DB::transaction(function () use ($tipoConjunto) {
                Prueba::where('tipo_conjunto', $tipoConjunto)->delete();
            });

            Notification::make()
                ->title('Conjunto eliminado')
                ->body('Todas las pruebas del conjunto han sido eliminadas exitosamente.')
                ->success()
                ->send();

            $this->reconstruirMatrices();
        });
}


public function editConjuntoAction(): ?Action
{
    if (! auth()->user()->can('editar_pruebas_conjuntas')) {
        return null;
    }

    return Action::make('editConjunto')
        ->label('Editar')
        ->icon('heroicon-o-pencil-square')
        ->color('primary')
        ->url(fn (array $arguments): string =>
            PruebaResource::getUrl('edit-conjunta', [
                'record' => $arguments['tipo_conjunto']
            ])
        );
}

   
    //boton para volver a la lista de pruebas unitarias
    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Volver a pruebas unitarias')
                ->url(PruebaResource::getUrl('index'))
                ->icon('heroicon-m-arrow-left'),
        ];
    }
}

