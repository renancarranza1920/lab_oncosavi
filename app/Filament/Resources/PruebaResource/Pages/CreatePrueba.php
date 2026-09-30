<?php

namespace App\Filament\Resources\PruebaResource\Pages;

use App\Filament\Resources\PruebaResource;
use App\Models\Prueba;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Throwable;

class CreatePrueba extends CreateRecord
{
    protected static string $resource = PruebaResource::class;

    public function create(bool $another = false): void
    {
        $data = $this->form->getState();
        $successMessages = [];

        try {
            DB::transaction(function () use ($data, &$successMessages) {
                if (! empty($data['nombre']) && ! empty($data['examen_id'])) {
                    $prueba = Prueba::create([
                        'nombre' => $data['nombre'],
                        'examen_id' => $data['examen_id'],
                        'tipo_prueba_id' => $data['tipo_prueba_id'] ?? null,
                    ]);

                    foreach ($data['valoresReferencia'] ?? [] as $valor) {
                        $prueba->valoresReferencia()->create(Arr::only($valor, [
                            'grupo_etario_id',
                            'genero',
                            'operador',
                            'valor_min',
                            'valor_max',
                            'unidades',
                            'descriptivo',
                            'nota',
                        ]));
                    }

                    $successMessages[] = "Se creo la prueba unitaria '{$data['nombre']}'.";
                }

                if (! empty($data['examen_id_conjunto']) && ! empty($data['filas']) && ! empty($data['columnas'])) {
                    $filas = (int) $data['filas'];
                    $columnas = (int) $data['columnas'];
                    $examenId = $data['examen_id_conjunto'];
                    $nombresFilas = $data['nombres_filas'] ?? [];
                    $nombresColumnas = $data['nombres_columnas'] ?? [];

                    $tipoConjuntoId = 'conjunto_' . uniqid();
                    $pruebasCreadas = 0;

                    for ($f = 1; $f <= $filas; $f++) {
                        for ($c = 1; $c <= $columnas; $c++) {
                            $nombreFila = trim($nombresFilas[$f] ?? "Fila {$f}");
                            $nombreColumna = trim($nombresColumnas[$c] ?? "Columna {$c}");
                            $nombrePrueba = "{$nombreFila}, {$nombreColumna}, ({$f}:{$c})";

                            Prueba::create([
                                'nombre' => $nombrePrueba,
                                'examen_id' => $examenId,
                                'tipo_prueba_id' => null,
                                'tipo_conjunto' => $tipoConjuntoId,
                            ]);

                            $pruebasCreadas++;
                        }
                    }

                    if ($pruebasCreadas > 0) {
                        $successMessages[] = "Se generaron {$pruebasCreadas} pruebas en conjunto.";
                    }
                }
            });

            if (empty($successMessages)) {
                Notification::make()
                    ->title('No se creo nada')
                    ->body('Por favor, completa los campos para una prueba unitaria o una matriz.')
                    ->warning()
                    ->send();

                return;
            }

            Notification::make()
                ->title('Creacion exitosa')
                ->body(implode("\n", $successMessages))
                ->success()
                ->send();
        } catch (Throwable $e) {
            Notification::make()
                ->title('Error al guardar')
                ->body('Ocurrio un error inesperado. No se guardo ningun dato. Detalles: ' . $e->getMessage())
                ->danger()
                ->send();

            $this->halt();
        }

        if ($another) {
            $this->form->fill();
        } else {
            $this->redirect($this->getResource()::getUrl('index'));
        }
    }
}
