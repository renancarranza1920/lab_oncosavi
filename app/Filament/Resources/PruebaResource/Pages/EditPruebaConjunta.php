<?php

namespace App\Filament\Resources\PruebaResource\Pages;
use App\Filament\Resources\PruebaResource;
use App\Models\Prueba;
use Filament\Actions\Action;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class EditPruebaConjunta extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = PruebaResource::class;
    protected static string $view = 'filament.resources.prueba-resource.pages.edit-prueba-conjunta';

    public ?string $record = null;
    
    // --- CAMBIO CLAVE 1: Hacer `originalData` público ---
    // Esto asegura que Livewire conserve los datos entre peticiones.
    public array $originalData = [];
    
    public array $data = [];

    public function mount(string $record): void
    {
        $this->record = $record;
        
        $pruebas = Prueba::where('tipo_conjunto', $this->record)->get();
        if ($pruebas->isEmpty()) {
            abort(404);
        }

        $filas = [];
        $columnas = [];
        foreach ($pruebas as $prueba) {
            $partes = explode(', ', $prueba->nombre);
            if (count($partes) >= 2) {
                $filas[] = $partes[0];
                $columnas[] = $partes[1];
            }
        }
        
        $nombresFilas = array_values(array_unique($filas));
        $nombresColumnas = array_values(array_unique($columnas));

        $formData = [
            'nombres_filas' => array_combine(range(1, count($nombresFilas)), $nombresFilas),
            'nombres_columnas' => array_combine(range(1, count($nombresColumnas)), $nombresColumnas),
        ];

        // Guardamos los datos originales y llenamos el formulario.
        $this->originalData = $formData;
        $this->form->fill($formData);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Card::make()
                ->schema([
                    Group::make()
                        ->schema(function (): array {
                            // Usamos los datos originales (que ahora persisten) para construir el formulario.
                            $filasData = $this->originalData['nombres_filas'] ?? [];
                            $columnasData = $this->originalData['nombres_columnas'] ?? [];
                            $filas = count($filasData);
                            $columnas = count($columnasData);

                            if ($filas === 0 || $columnas === 0) {
                                return [Placeholder::make('no_data')->content('No se pudo reconstruir la matriz.')];
                            }

                            $matrixComponents = [];
                            $headerComponents = [Placeholder::make('top_left_corner')->label('')->content(new HtmlString('&nbsp;'))];
                            for ($c = 1; $c <= $columnas; $c++) {
                                $headerComponents[] = TextInput::make("nombres_columnas.{$c}")->label("Columna {$c}");
                            }
                            $matrixComponents[] = Grid::make($columnas + 1)->schema($headerComponents);

                            for ($f = 1; $f <= $filas; $f++) {
                                $matrixComponents[] = Grid::make($columnas + 1)
                                    ->schema([
                                        TextInput::make("nombres_filas.{$f}")->label("Fila {$f}")->columnSpan(1),
                                    ]);
                            }
                            return $matrixComponents;
                        }),
                ])
        ])->statePath('data');
    }

    // --- CAMBIO CLAVE 2: Lógica de guardado robusta ---
    public function save(): void
    {
        $newData = $this->form->getState();

        try {
            DB::transaction(function () use ($newData) {
                $originalFilas = $this->originalData['nombres_filas'] ?? [];
                $originalColumnas = $this->originalData['nombres_columnas'] ?? [];

                $nuevasFilas = $newData['nombres_filas'] ?? [];
                $nuevasColumnas = $newData['nombres_columnas'] ?? [];

                // Obtenemos todas las pruebas del conjunto una sola vez
                $pruebas = Prueba::where('tipo_conjunto', $this->record)->get();

                foreach ($pruebas as $prueba) {
                    $partes = explode(', ', $prueba->nombre);
                    if (count($partes) < 2) continue;

                    $nombreFilaOriginal = $partes[0];
                    $nombreColumnaOriginal = $partes[1];
                    $coordenadas = $partes[2] ?? null;

                    // Buscamos cuál debería ser el nuevo nombre de la fila
                    $filaIndex = array_search($nombreFilaOriginal, $originalFilas);
                    $nuevoNombreFila = ($filaIndex !== false && isset($nuevasFilas[$filaIndex]))
                        ? $nuevasFilas[$filaIndex]
                        : $nombreFilaOriginal;

                    // Buscamos cuál debería ser el nuevo nombre de la columna
                    $columnaIndex = array_search($nombreColumnaOriginal, $originalColumnas);
                    $nuevoNombreColumna = ($columnaIndex !== false && isset($nuevasColumnas[$columnaIndex]))
                        ? $nuevasColumnas[$columnaIndex]
                        : $nombreColumnaOriginal;
                    
                    // Reconstruimos el nombre completo
                    $nuevoNombreCompleto = trim("{$nuevoNombreFila}, {$nuevoNombreColumna}" . ($coordenadas ? ", {$coordenadas}" : ''));

                    // Actualizamos solo si ha habido un cambio
                    if ($prueba->nombre !== $nuevoNombreCompleto) {
                        $prueba->nombre = $nuevoNombreCompleto;
                        $prueba->save();
                    }
                }
            });
        } catch (\Throwable $e) {
            Notification::make()->title('Error al actualizar')->body($e->getMessage())->danger()->send();
            return;
        }
        
        Notification::make()->title('Matriz actualizada exitosamente')->success()->send();
        $this->redirect(ListPruebasConjuntas::getUrl());
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')->label('Guardar Cambios')->submit('save'),
            Action::make('cancel')->label('Cancelar')->url(ListPruebasConjuntas::getUrl())->color('gray'),
        ];
    }
}

