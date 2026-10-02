<?php

namespace App\Filament\Resources\OrdenResource\Pages;

use App\Filament\Resources\OrdenResource;
use App\Models\Examen;
use App\Models\GrupoEtario;
use App\Models\Orden;
use App\Models\Prueba; // Asegúrate de importar el modelo
use Filament\Actions\Action;
use Filament\Forms\Components\View;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Filament\Actions\ActionGroup;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class IngresarResultados extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = OrdenResource::class;
    protected static string $view = 'filament.resources.orden-resource.pages.ingresar-resultados';

    public ?Orden $record;
    public array $data = [];

    public function mount(Orden $record): void
    {
        abort_unless(static::getResource()::canView($record), 404);
        abort_unless(auth()->user()->can('ingresar_resultados_orden'), 403);
        $this->record = $record;
        $this->form->fill($this->prepareInitialData());
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            View::make('filament.resources.orden-resource.pages.ingresar-resultados-view')
                ->statePath('resultados_examenes'),
        ])->statePath('data');
    }

    // En app/Filament/Resources/OrdenResource/Pages/IngresarResultados.php

public function isOrderComplete(): bool
{
    // 1. Traemos los detalles para iterar uno por uno
    $detalles = $this->record->detalleOrden()
        ->whereNotNull('examen_id')
        // Cargamos la relación para no hacer mil consultas
        ->with(['examen', 'orden.resultados']) 
        ->get();

    $totalRequeridas = 0;
    $totalCompletadas = 0;

    foreach ($detalles as $detalle) {
        // Saltamos los externos porque esos se manejan diferente
        if ($detalle->examen && $detalle->examen->es_externo) continue;

        // A. DETERMINAR QUÉ PRUEBAS PIDE EL SNAPSHOT ACTUAL
        $idsRequeridos = [];
        
        if (!empty($detalle->pruebas_snapshot)) {
            // Si hay foto, sacamos los IDs de la foto
            $idsRequeridos = collect($detalle->pruebas_snapshot)->pluck('id')->toArray();
        } elseif ($detalle->examen) {
            // Si es vieja, sacamos los IDs activos de la BD
            $idsRequeridos = $detalle->examen->pruebas
                ->where('estado', 'activo') // Tu filtro personalizado
                ->pluck('id')
                ->toArray();
        }

        if (empty($idsRequeridos)) continue;

        // Sumamos al contador global de lo que se necesita
        $totalRequeridas += count($idsRequeridos);

        // B. CONTAR RESULTADOS VÁLIDOS (LA CORRECCIÓN MÁGICA)
        // Usamos la colección de resultados en memoria ($this->record->resultados)
        // y filtramos para contar SOLO los que coinciden con los IDs requeridos.
        $completadasEnEsteDetalle = $this->record->resultados
            ->where('detalle_orden_id', $detalle->id)
            ->whereIn('prueba_id', $idsRequeridos) // <--- ¡AQUÍ ESTÁ EL TRUCO! Ignoramos los huérfanos
            ->whereNotNull('resultado')
            ->where('resultado', '!=', '')
            ->count();

        $totalCompletadas += $completadasEnEsteDetalle;
    }

    // Si por alguna razón no se requieren pruebas, asumimos completo
    if ($totalRequeridas === 0) return true;

    // Ahora sí: 1 requerido === 1 completado válido (aunque sobren huérfanos)
    return $totalRequeridas === $totalCompletadas;
}

protected function prepareInitialData(): array
{
    // Cargamos relaciones incluyendo tipoPrueba
    $detalles = $this->record->detalleOrden()
        ->with([
            'examen.tipoExamen',
            'examen.pruebas.tipoPrueba',
            'examen.pruebas.valoresReferencia.grupoEtario'
        ]) 
        ->get();
        
    $preparedData = [];

    foreach ($detalles as $detalle) {
        if (!$detalle->examen) continue;

        // --- LÓGICA HÍBRIDA (SNAPSHOT VS BD) ---
        $todasLasPruebas = !empty($detalle->pruebas_snapshot) 
            ? collect($detalle->pruebas_snapshot)->map(fn($item) => json_decode(json_encode($item)))
            : collect();

        // Clasificación
        $pruebasUnitariasRaw = $todasLasPruebas->whereNull('tipo_conjunto');
        $pruebasConjuntas = $todasLasPruebas->whereNotNull('tipo_conjunto')->groupBy('tipo_conjunto');

        // Mapeo de Unitarias incluyendo el nombre del Tipo de Prueba
        $dataUnitarias = $pruebasUnitariasRaw->map(function($prueba) use ($detalle) {
            $data = $this->getPruebaData($prueba, $detalle->id);
            
            // Buscamos el nombre del tipo de prueba en la relación cargada
            $pruebaOriginal = $detalle->examen->pruebas->where('id', $prueba->id)->first();
            $data['tipo_prueba_nombre'] = $pruebaOriginal?->tipoPrueba?->nombre ?? 'General';
            
            return $data;
        })->values()->all();
        
        // Matrices
        $dataMatrices = $pruebasConjuntas->map(function ($pruebasDelConjunto) use ($detalle) {
            $filas = []; $columnas = []; $dataMatrix = [];
            foreach ($pruebasDelConjunto as $prueba) {
                $partes = explode(', ', $prueba->nombre);
                if (count($partes) >= 2) {
                    [$nombreFila, $nombreColumna] = $partes;
                    $filas[] = $nombreFila; $columnas[] = $nombreColumna;
                    $dataMatrix[$nombreFila][$nombreColumna] = $this->getPruebaData($prueba, $detalle->id);
                }
            }
            return [
                'filas' => array_values(array_unique($filas)),
                'columnas' => array_values(array_unique($columnas)),
                'data' => $dataMatrix
            ];
        })->all();
       


        // Externos (Mantenemos tu lógica igual)
        $resultadosExternosDB = $this->record->resultados()
            ->where('detalle_orden_id', $detalle->id)
            ->where('es_externo', true)
            ->get();

        $dataExternos = [];
        foreach ($resultadosExternosDB as $resExt) {
            $dataExternos[] = [
                'id' => $resExt->id,
                'temp_id' => Str::uuid()->toString(),
                'prueba_nombre' => $resExt->prueba_nombre_snapshot,
                'resultado' => $resExt->resultado,
                'valor_referencia' => $resExt->valor_referencia_snapshot,
                'unidades' => $resExt->unidades_snapshot,
                'alertar' => $resExt->alertar,
            ];
        }

        $preparedData[$detalle->id] = [
            'examen_nombre' => $detalle->nombre_examen,
            'es_referido' => (bool) ($detalle->examen->es_externo ?? false),
            'pruebas_unitarias' => $dataUnitarias, // Lista plana para preservar índices
            'tipo_examen' => $detalle->examen->tipoExamen->nombre ?? 'Otros',
            'matrices' => $dataMatrices,
            'externos' => $dataExternos,
        ];
    }
   // dd($preparedData);

    return ['resultados_examenes' => $preparedData];
}

    protected function getPruebaData($prueba, int $detalleId): array
{
    // 1. Buscamos si ya hay un resultado guardado
    $resultadoExistente = $this->record->resultados()
        ->where('prueba_id', $prueba->id)
        ->where('detalle_orden_id', $detalleId)
        ->first();

    $referencia = ''; 
    $unidades = '';
    $notas = '';
    $esAlerta = false; 
    $mensajeAlerta = '';

    // 2. Obtener la lista de todos los valores de referencia posibles
    $todosLosValores = collect([]);

    if (isset($prueba->valores_referencia)) {
        // Viene del Snapshot (JSON)

        $todosLosValores = collect($prueba->valores_referencia);
       // dd($todosLosValores);
   $notas = collect($prueba->valores_referencia)
    ->map(fn($v) => $v->nota ?? null)
    ->filter()
    ->unique()
    ->implode('<br>');

        $unidades = $todosLosValores->first()->unidades ?? ''; 
    } elseif ($prueba instanceof \App\Models\Prueba) {
        // Viene de la BD (Orden vieja o sin snapshot)
        $todosLosValores = $prueba->valoresReferencia;
        $notas = $todosLosValores
            ->map(fn($v) => $v->nota ?? null)
            ->filter()
            ->unique()
            ->implode('<br>');
        
    }

    // 3. Lógica de Selección Prioritaria (El corazón del cambio)
    if ($todosLosValores->isNotEmpty()) {
        $cliente = $this->record->cliente;
        $generoCliente = $cliente->genero; // 'Masculino' o 'Femenino'
        $valorRef = null;

        // --- PRE-FILTRO: Caso Especial Embarazo (Prioridad Absoluta) ---
        if (isset($this->record->semanas_gestacion) && $this->record->semanas_gestacion) {
             $grupoEmbarazo = \App\Models\GrupoEtario::where('unidad_tiempo', 'semanas')
                ->where('edad_min', '<=', $this->record->semanas_gestacion)
                ->where('edad_max', '>=', $this->record->semanas_gestacion)
                ->first();

            if ($grupoEmbarazo) {
                $valorRef = $todosLosValores->first(function($val) use ($grupoEmbarazo) {
                    $gid = is_array($val) ? ($val['grupo_etario_id'] ?? null) : $val->grupo_etario_id;
                    return $gid == $grupoEmbarazo->id;
                });
            }
        }

        // Si no es embarazo, seguimos la lógica estándar 1-2-3-4
        if (!$valorRef) {
            
            // Paso A: Obtener el Grupo Etario Específico (Ej: Adultos)
            $grupoEtarioCliente = $cliente->getGrupoEtario(); 
           // dd($grupoEtarioCliente);

            // Paso B: Obtener el Grupo "Todas las edades" (Ej: 0-120 años)
            $grupoTodasEdades = \App\Models\GrupoEtario::where('nombre', 'Todas las edades')
                ->orWhere(function($query) {
                    $query->where('edad_min', 0)->where('edad_max', '>=', 120);
                })->first();

           // ... (código anterior donde obtienes $todosLosValores, $cliente, etc.)

// --- INTENTO 1: Grupo Específico + Género Exacto ---
if ($grupoEtarioCliente) {
    $valoresRef = $todosLosValores->filter(function($val) use ($grupoEtarioCliente, $generoCliente) {
        $gid = is_array($val) ? ($val['grupo_etario_id'] ?? null) : $val->grupo_etario_id;
        $gen = is_array($val) ? ($val['genero'] ?? null) : $val->genero;
        return $gid == $grupoEtarioCliente->id && $gen == $generoCliente;
    });

    if ($valoresRef->isNotEmpty()) {
        $valorRef = $valoresRef->count() === 1 ? $valoresRef->first() : $valoresRef;
    }
}

// --- INTENTO 2: Grupo Específico + "Ambos" ---
if (!$valorRef && $grupoEtarioCliente) {
    $valoresRef = $todosLosValores->filter(function($val) use ($grupoEtarioCliente) {
        $gid = is_array($val) ? ($val['grupo_etario_id'] ?? null) : $val->grupo_etario_id;
        $gen = is_array($val) ? ($val['genero'] ?? null) : $val->genero;
        return $gid == $grupoEtarioCliente->id && $gen == 'Ambos';
    });

    if ($valoresRef->isNotEmpty()) {
        $valorRef = $valoresRef->count() === 1 ? $valoresRef->first() : $valoresRef;
    }
}

// --- INTENTO 3: Grupo "Todas las edades" + Género Exacto ---
if (!$valorRef && $grupoTodasEdades) {
    $valoresRef = $todosLosValores->filter(function($val) use ($grupoTodasEdades, $generoCliente) {
        $gid = is_array($val) ? ($val['grupo_etario_id'] ?? null) : $val->grupo_etario_id;
        $gen = is_array($val) ? ($val['genero'] ?? null) : $val->genero;
        return $gid == $grupoTodasEdades->id && $gen == $generoCliente;
    });

    if ($valoresRef->isNotEmpty()) {
        $valorRef = $valoresRef->count() === 1 ? $valoresRef->first() : $valoresRef;
    }
}

// --- INTENTO 4: Grupo "Todas las edades" + "Ambos" ---
if (!$valorRef && $grupoTodasEdades) {
    $valoresRef = $todosLosValores->filter(function($val) use ($grupoTodasEdades) {
        $gid = is_array($val) ? ($val['grupo_etario_id'] ?? null) : $val->grupo_etario_id;
        $gen = is_array($val) ? ($val['genero'] ?? null) : $val->genero;
        return $gid == $grupoTodasEdades->id && $gen == 'Ambos';
    });

    if ($valoresRef->isNotEmpty()) {
        $valorRef = $valoresRef->count() === 1 ? $valoresRef->first() : $valoresRef;
    }
}

        }

        // 4. Resultado del Proceso
        if ($valorRef) 
        
         if ($valorRef instanceof \Illuminate\Support\Collection) {

        $referencias = [];

        foreach ($valorRef as $ref) {

    $vMin  = is_array($ref) ? ($ref['valor_min'] ?? null) : $ref->valor_min;
    $vMax  = is_array($ref) ? ($ref['valor_max'] ?? null) : $ref->valor_max;
    $vOp   = is_array($ref) ? ($ref['operador'] ?? null) : $ref->operador;
    $vDesc = is_array($ref) ? ($ref['descriptivo'] ?? '') : ($ref->descriptivo ?? '');
    $vUni  = is_array($ref) ? ($ref['unidades'] ?? '') : ($ref->unidades ?? '');

    $valorMin = !is_null($vMin) ? rtrim(rtrim(number_format((float)$vMin, 2, '.', ''), '0'), '.') : null;
    $valorMax = !is_null($vMax) ? rtrim(rtrim(number_format((float)$vMax, 2, '.', ''), '0'), '.') : null;

    $refTexto = match ($vOp) {
        'rango' => "{$valorMin} - {$valorMax}",
        '<=' => "≤ {$valorMax}",
        '<' => "< {$valorMax}",
        '>=' => "≥ {$valorMin}",
        '>' => "> {$valorMin}",
        '=' => "= {$valorMin}",
        default => '',
    };

    $referencias[] = trim("{$vDesc} {$refTexto}");

    if (empty($unidades)) {
        $unidades = $vUni;
    }
}


       $referencia = implode('<br>', $referencias);


    } else{
            // --- ÉXITO: Formateamos el valor encontrado ---
            $vMin = is_array($valorRef) ? $valorRef['valor_min'] : $valorRef->valor_min;
            $vMax = is_array($valorRef) ? $valorRef['valor_max'] : $valorRef->valor_max;
            $vOp = is_array($valorRef) ? $valorRef['operador'] : $valorRef->operador;
            $vDesc = is_array($valorRef) ? ($valorRef['descriptivo'] ?? null) : ($valorRef->descriptivo ?? null);
            $vUni = is_array($valorRef) ? ($valorRef['unidades'] ?? '') : ($valorRef->unidades ?? '');

            $valorMin = rtrim(rtrim(number_format((float)$vMin, 2, '.', ''), '0'), '.');
            $valorMax = rtrim(rtrim(number_format((float)$vMax, 2, '.', ''), '0'), '.');

            $referencia = match ($vOp) {
                'rango' => "$valorMin - $valorMax",
                '<=' => "≤ $valorMax",
                '<' => "< $valorMax",
                '>=' => "≥ $valorMin",
                '>' => "> $valorMin",
                '=' => "= $valorMin",
                default => $vDesc ?? "$valorMin - $valorMax",
            };
            
            if ($vDesc && !str_contains($referencia, $vDesc)) {
                $referencia = "{$vDesc} {$referencia}";
            }

            if (empty($unidades)) $unidades = $vUni;
            
        } else {
            // --- FALLO: Activamos la Alerta ---
            $esAlerta = true;
            $referencia = "N/A"; 
            
            $grupoNombre = $cliente->getGrupoEtario()?->nombre ?? 'Edad sin clasificar';
            $mensajeAlerta = "Falta conf. para: {$grupoNombre} ({$generoCliente})";
        }

    } else {
         // No hay valores de referencia configurados para la prueba.
         $esAlerta = false; 
         $referencia = '';
    }

    return [
        'prueba_id' => $prueba->id,
        'prueba_nombre' => $prueba->nombre,
        'resultado_id' => $resultadoExistente?->id,
        'resultado' => $resultadoExistente?->resultado,
        'valor_referencia' => $referencia,
        'unidades' => $unidades,
        'notas' => $notas,
        'es_alerta' => $esAlerta,
        'mensaje_alerta' => $mensajeAlerta,
        'alertar' => $resultadoExistente?->alertar ?? false,
    ];
    
    
}
    public function addExternalRow($detalleId)
    {
        $this->data['resultados_examenes'][$detalleId]['externos'][] = [
            'id' => null,
            'temp_id' => Str::uuid()->toString(),
            'prueba_nombre' => '',
            'resultado' => '',
            'valor_referencia' => '',
            'notas' =>'',
            'unidades' => '',
            'alertar' => false,
        ];
    }

    public function removeExternalRow($detalleId, $index)
    {
        abort_unless(auth()->user()?->can('ingresar_resultados_orden'), 403);
        abort_unless($this->record->detalleOrden()->whereKey($detalleId)->exists(), 404);

        $fila = $this->data['resultados_examenes'][$detalleId]['externos'][$index] ?? null;
        if (!$fila) {
            return;
        }

        if (!empty($fila['id'])) {
            $resultado = $this->record->resultados()
                ->where('resultados.id', $fila['id'])
                ->where('detalle_orden_id', $detalleId)
                ->where('es_externo', true)
                ->firstOrFail();
            $resultado->delete();
            $this->eliminarPdfParcial();
        }
        unset($this->data['resultados_examenes'][$detalleId]['externos'][$index]);
        // Mantener los índices evita cambiar los enlaces wire:model de las filas restantes.
    }

    protected function getFormActions(): array
    {
        return [Action::make('save')->label('Guardar Resultados')->submit('save')->visible(fn() => auth()->user()->can('ingresar_resultados_orden')),
         Action::make('completar')
            ->label('Completar Orden')
            ->color('success')
            ->icon('heroicon-o-check-circle')
            //->visible(fn(): bool => $this->isOrderComplete())
            ->requiresConfirmation()
            ->action(function () {

                // Primero guardamos por seguridad
                $this->save();

                $this->record->estado = 'finalizado';
                $this->record->save();
               

                Notification::make()
                    ->title('Orden Completada')
                    ->success()
                    ->send();

                return redirect(static::getResource()::getUrl('index'));
            }),
             
            ActionGroup::make([

    Action::make('generar_pdf_parcial')
        ->label('Generar PDF')
        ->icon('heroicon-o-printer')
        ->color('gray')
        ->visible(fn() => $this->tieneExamenesParaParcial())
        ->action(fn() => $this->generarPdfParcial()),

    Action::make('enviar_pdf_parcial')
    ->label('Enviar')
    ->icon('heroicon-o-paper-airplane')
    ->color('primary')
    ->visible(function () {
        $nombreCliente = \Illuminate\Support\Str::slug(
            $this->record->cliente->nombre . ' ' . $this->record->cliente->apellido
        );

        $fileName = strtoupper("{$nombreCliente} - {$this->record->id} P.pdf");

        return Storage::disk('public')
            ->exists("reportes/{$fileName}");
    })
    ->action(fn() => $this->enviarPdfParcial()),
        
        ])
        ->label('PDF')
        ->icon('heroicon-o-document-text')
        ->color('gray')
        ->button(),
        ];
        
        
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reconstruir')
                ->label('Sincronizar con Catálogo')
                ->color('warning')
                ->icon('heroicon-o-arrow-path')
                ->visible(fn() => !in_array($this->record->estado, ['finalizado', 'cancelado']))
                ->requiresConfirmation()
                ->modalHeading('¿Actualizar definición de pruebas?')
                ->modalDescription('Esto actualizará la orden con la configuración actual del catálogo (nombres y valores de referencia).')
                ->action(function () {
                    foreach ($this->record->detalleOrden as $detalle) {
                        if ($detalle->examen_id) {
                            
                             // 🔥 LIMPIEZA INTELIGENTE
                            if ($detalle->examen && !$detalle->examen->es_externo) {
                    
                                $this->record->resultados()
                                    ->where('detalle_orden_id', $detalle->id)
                                    ->where('es_externo', true)
                                    ->delete();
                            }
                            
                            // 1. Cargamos la data FRESCA con la relación correcta
                            $examenFresco = Examen::with([
                                'pruebas.valoresReferencia',
                            ])->find($detalle->examen_id);

                            if ($examenFresco) {
                                // 2. Generamos el NUEVO SNAPSHOT
                                $nuevoSnapshot = $examenFresco->pruebas->where('estado', 'activo')->map(function ($prueba) {
                                    $data = [
                                        'id' => $prueba->id,
                                        'nombre' => $prueba->nombre,
                                        'tipo_conjunto' => $prueba->tipo_conjunto,
                                        'tipo_prueba_id' => $prueba->tipo_prueba_id,
                                        'valores_referencia' => [],
                                    ];

                                    // CORRECCIÓN: Usamos la colección y sacamos el primero
                                    $data['valores_referencia'] = $prueba->valoresReferencia
                                            ->filter(function ($val) use ($prueba) {
                                                return $val->prueba_id === $prueba->id || is_null($val->prueba_id);
                                            })
                                            ->map(function ($val) {
                                                return [
                                                    'grupo_etario_id' => $val->grupo_etario_id,
                                                    'genero' => $val->genero,
                                                    'valor_min' => $val->valor_min,
                                                    'valor_max' => $val->valor_max,
                                                    'nota' => $val->nota, 
                                                    'operador' => $val->operador,
                                                    'unidades' => $val->unidades,
                                                    'descriptivo' => $val->descriptivo,
                                                ];
                                            })->values()->toArray();
                                    return $data;
                                })->toArray();

                                // 3. Guardamos
                                $detalle->update(['pruebas_snapshot' => $nuevoSnapshot]);
                            }
                        }
                    }
                    Notification::make()->title('Orden sincronizada')->success()->send();
                    return redirect(request()->header('Referer'));
                }),

            Action::make('completar')->label('Completar Orden')->color('success')->icon('heroicon-o-check-circle')
                //->visible(fn(): bool => $this->isOrderComplete())
                ->requiresConfirmation()
                ->action(function () {
                        $this->save();
                    $this->record->estado = 'finalizado';
                    $this->record->save();
                    Notification::make()->title('Orden Completada')->success()->send();
                    return redirect(static::getResource()::getUrl('index'));
                }),
            Action::make('regresar')->label('Regresar a Órdenes')->color('gray')->icon('heroicon-o-arrow-left')
                ->url(static::getResource()::getUrl('index')),
                
            
            
            ActionGroup::make([

    Action::make('generar_pdf_parcial')
        ->label('Generar PDF')
        ->icon('heroicon-o-printer')
        ->color('gray')
        ->visible(fn() => $this->tieneExamenesParaParcial())
        ->action(fn() => $this->generarPdfParcial()),

    Action::make('enviar_pdf_parcial')
    ->label('Enviar')
    ->icon('heroicon-o-paper-airplane')
    ->color('primary')
    ->visible(function () {
        $nombreCliente = \Illuminate\Support\Str::slug(
            $this->record->cliente->nombre . ' ' . $this->record->cliente->apellido
        );

        $fileName = strtoupper("{$nombreCliente} - {$this->record->id} P.pdf");

        return Storage::disk('public')
            ->exists("reportes/{$fileName}");
    })
    ->action(fn() => $this->enviarPdfParcial()),
        
        ])
        ->label('PDF')
        ->icon('heroicon-o-document-text')
        ->color('gray')
        ->button(),
            
            
        ];
    }

    public function save(): void
    {
        $formData = $this->form->getState()['resultados_examenes'];
     //dd($this->form->getState());
        foreach ($formData as $detalleId => $examenData) {
            foreach (($examenData['pruebas_unitarias'] ?? []) as $r) {
                $this->guardarResultado($detalleId, $r);
            }
            foreach (($examenData['matrices'] ?? []) as $m) {
                foreach ($m['data'] as $f) {
                    foreach ($f as $c) {
                        $this->guardarResultado($detalleId, $c);
                    }
                }
            }
            foreach (($examenData['externos'] ?? []) as $ext) {
                if (!empty($ext['prueba_nombre']) && !empty($ext['resultado'])) {
                    $this->guardarResultadoExterno($detalleId, $ext);
                }
            }
        }
        Notification::make()->title('Resultados guardados')->success()->send();
        $this->form->fill($this->prepareInitialData());
    }

protected function guardarResultado(int $detalleId, array $r): void
{
    $valorResultado = $r['resultado'] ?? null;
    $alertar = (bool) ($r['alertar'] ?? false);

    $resultadoExistente = $this->record->resultados()
        ->where('detalle_orden_id', $detalleId)
        ->where('prueba_id', $r['prueba_id'])
        ->first();

    if ($resultadoExistente) {

        // Siempre actualizamos aunque no cambie el resultado
        $resultadoExistente->update([
            'resultado' => $valorResultado,
            'user_id' => auth()->id(),
            'prueba_nombre_snapshot' => $r['prueba_nombre'],
            'valor_referencia_snapshot' => $r['valor_referencia'],
            'unidades_snapshot' => $r['unidades'],
            'es_externo' => 0,
            'alertar' => $alertar,
        ]);

        $this->eliminarPdfParcial();

    } elseif (!is_null($valorResultado) && $valorResultado !== '') {

        // Solo creamos si hay resultado real
        $this->record->resultados()->create([
            'detalle_orden_id' => $detalleId,
            'prueba_id' => $r['prueba_id'],
            'resultado' => $valorResultado,
            'user_id' => auth()->id(),
            'prueba_nombre_snapshot' => $r['prueba_nombre'],
            'valor_referencia_snapshot' => $r['valor_referencia'],
            'unidades_snapshot' => $r['unidades'],
            'es_externo' => 0,
            'alertar' => $alertar,
        ]);

        $this->eliminarPdfParcial();
    }
}

protected function guardarResultadoExterno(int $detalleId, array $ext): void
{
    $dataToSave = [
        'detalle_orden_id' => $detalleId,
        'prueba_id' => null,
        'prueba_nombre_snapshot' => $ext['prueba_nombre'],
        'resultado' => $ext['resultado'],
        'user_id' => auth()->id(),  // Asignamos el user_id actual
        'valor_referencia_snapshot' => $ext['valor_referencia'],
        'unidades_snapshot' => $ext['unidades'],
        'es_externo' => 1,
        'alertar' => (bool) ($ext['alertar'] ?? false),
    ];

    if (!empty($ext['id'])) {
        // Si el resultado externo ya existe, lo actualizamos
       \App\Models\Resultado::where('id', $ext['id'])->update($dataToSave);
        $this->eliminarPdfParcial();
    } else {
        // Si no existe, lo creamos como un nuevo resultado
        $this->record->resultados()->create($dataToSave);
        $this->eliminarPdfParcial();
    }
}


    public function deleteResultado($resultadoId): void
    {
        $resultado = \App\Models\Resultado::find($resultadoId);
        if ($resultado && $resultado->detalleOrden->orden_id === $this->record->id) {
            $resultado->delete();
            $this->eliminarPdfParcial();
            Notification::make()->title('Resultado eliminado')->success()->send();
            $this->form->fill($this->prepareInitialData());
        }
    }

    
    //lógica nueva para generar pdf parcial
    protected function getExamenesCompletosParcial()
{
    $orden = $this->record->load([
        'detalleOrden.examen.pruebas',
        'resultados'
    ]);

    $examenesCompletos = [];

    foreach ($orden->detalleOrden as $detalle) {

        if (!$detalle->examen ) {
            continue;
        }
        
         if ($detalle->examen->es_externo) {

        $externos = $orden->resultados
            ->where('detalle_orden_id', $detalle->id)
            ->where('es_externo', true)
            ->whereNotNull('resultado')
            ->where('resultado', '!=', '');

        if ($externos->count() > 0) {
            $examenesCompletos[] = $detalle;
        }

        continue;
         }

        $idsRequeridos = !empty($detalle->pruebas_snapshot)
            ? collect($detalle->pruebas_snapshot)->pluck('id')->toArray()
            : $detalle->examen->pruebas->pluck('id')->toArray();
        
        if (empty($idsRequeridos)) {
            continue;
        }

        $resultadosValidos = $orden->resultados
            ->where('detalle_orden_id', $detalle->id)
            ->whereIn('prueba_id', $idsRequeridos)
            ->whereNotNull('resultado')
            ->where('resultado', '!=', '');

        if ($resultadosValidos->count() === count($idsRequeridos)) {
            $examenesCompletos[] = $detalle;
        }
    }

    return collect($examenesCompletos);
}


protected function tieneExamenesParaParcial(): bool
{
    return $this->getExamenesCompletosParcial()->isNotEmpty();
}

public function generarPdfParcial()
{
    $this->save();

    $examenesCompletos = $this->getExamenesCompletosParcial();

    if ($examenesCompletos->isEmpty()) {
        Notification::make()
            ->title('No hay exámenes completos')
            ->danger()
            ->send();
        return;
    }

    $orden = $this->record->load([
        'cliente',
        'medico',
        'detalleOrden.examen.tipoExamen',
        'detalleOrden.examen.pruebas.tipoPrueba',
        'resultados'
    ]);
    $resultadosPorDetallePrueba = $orden->resultados
        ->keyBy(fn($res) => $res->detalle_orden_id . ':' . $res->prueba_id);
    
    

    // 🔥 FILTRAR SOLO DETALLES COMPLETOS
    $orden->setRelation(
        'detalleOrden',
        $orden->detalleOrden->whereIn('id', $examenesCompletos->pluck('id'))
    );

    // 🔥 FILTRAR SOLO RESULTADOS VÁLIDOS
    $orden->setRelation(
        'resultados',
        $orden->resultados
            ->whereNotNull('resultado')
            ->where('resultado', '!=', '')
    );

    // ---------------------------------------------------------
    // 1️⃣ CONSTRUIR CONTENIDO (MISMA LÓGICA DEL BOTÓN FINAL)
    // ---------------------------------------------------------

    $procesarPrueba = function ($prueba, $orden, $detalleId) use ($resultadosPorDetallePrueba) {
        $pruebaId = $prueba->id ?? null;
        $resultado = $pruebaId
            ? $resultadosPorDetallePrueba->get($detalleId . ':' . $pruebaId)
            : null;

        if (!$resultado) return null; // 🔥 SIN FALLBACK

        return [
            'nombre' => $resultado->prueba_nombre_snapshot ?? $prueba->nombre,
            'resultado' => $resultado->resultado,
            'referencia' => $resultado->valor_referencia_snapshot ?? '',
            'unidades' => $resultado->unidades_snapshot ?? '',
            'fecha_resultado' => $resultado->updated_at->format('d/m/Y'),
            'alertar' => (bool) $resultado->alertar,
            'es_fuera_de_rango' => (bool) $resultado->fuera_de_rango,
            'user_id' => $resultado->user_id,
            'tipo_prueba' => $prueba->tipoPrueba->nombre ?? '',
        ];
    };

    $detallesAgrupados = $orden->detalleOrden
        ->whereNotNull('examen_id')
        ->groupBy('examen.tipoExamen.nombre');

    $contenido_maestro = [];

    foreach ($detallesAgrupados as $tipoExamenNombre => $detalles) {

        $examenes_data = [];

       foreach ($detalles as $detalle) {

            // 🔥 Si es externo, lo metemos directamente en pruebas_unitarias (Igual que en OrdenResource)
            if ($detalle->examen->es_externo) {
                $resultadosExternos = $orden->resultados
                    ->where('detalle_orden_id', $detalle->id)
                    ->where('es_externo', 1)
                    ->whereNotNull('resultado')
                    ->where('resultado', '!=', '')
                    ->map(function ($res) {
                        return [
                            'nombre' => $res->prueba_nombre_snapshot ?? 'Prueba Externa',
                            'resultado' => $res->resultado,
                            'referencia' => $res->valor_referencia_snapshot ?? $res->valor_referencia_externo ?? '',
                            'unidades' => $res->unidades_snapshot ?? '',
                            'fecha_resultado' => $res->updated_at->format('d/m/Y'),
                            'alertar' => (bool) $res->alertar,
                            'es_fuera_de_rango' => (bool) $res->fuera_de_rango,
                            'user_id' => $res->user_id,
                            'tipo_prueba' => '',
                        ];
                    })->values()->all();

                if (!empty($resultadosExternos)) {
                    $examenes_data[] = [
                        'nombre' => $detalle->nombre_examen ?? $detalle->examen->nombre,
                        // 👇 LA MAGIA ESTÁ AQUÍ: Lo pasamos como pruebas_unitarias
                        'pruebas_unitarias' => $resultadosExternos, 
                        'matrices' => [],
                        'externos' => [],
                    ];
                }
                
                continue; // Terminamos con este detalle y saltamos al siguiente
            }

            // 👇 LÓGICA NORMAL PARA EXÁMENES INTERNOS 👇
            $todasLasPruebas = !empty($detalle->pruebas_snapshot)
                ? collect($detalle->pruebas_snapshot)->map(fn($item) => json_decode(json_encode($item)))
                : $detalle->examen->pruebas;

            $pruebasUnitarias = $todasLasPruebas->whereNull('tipo_conjunto');
            $pruebasConjuntas = $todasLasPruebas->whereNotNull('tipo_conjunto')->groupBy('tipo_conjunto');

            $dataUnitarias = $pruebasUnitarias
                ->map(fn($p) => $procesarPrueba($p, $orden, $detalle->id))
                ->filter()
                ->values()
                ->all();

            $dataMatrices = $pruebasConjuntas->map(function ($pruebasDelConjunto) use ($procesarPrueba, $orden, $detalle) {
                $f = []; $c = []; $m = [];
                foreach ($pruebasDelConjunto as $p) {
                    $partes = explode(', ', $p->nombre);
                    if (count($partes) >= 2) {
                        [$nf, $nc] = $partes;
                        $celda = $procesarPrueba($p, $orden, $detalle->id);
                        if ($celda) {
                            $f[] = $nf;
                            $c[] = $nc;
                            $m[$nf][$nc] = $celda;
                        }
                    }
                }
                if (empty($m)) return null;
                return [
                    'filas' => array_values(array_unique($f)),
                    'columnas' => array_values(array_unique($c)),
                    'data' => $m
                ];
            })->filter()->values()->all();

            if (!empty($dataUnitarias) || !empty($dataMatrices)) {
                $examenes_data[] = [
                    'nombre' => $detalle->nombre_examen ?? $detalle->examen->nombre,
                    'pruebas_unitarias' => $dataUnitarias,
                    'matrices' => $dataMatrices,
                    'externos' => [],
                ];
            }
        }

        if (!empty($examenes_data)) {
            $contenido_maestro[$tipoExamenNombre ?: 'Exámenes Generales'] = $examenes_data;
        }
    }

    // ---------------------------------------------------------
    // 2️⃣ REAGRUPACIÓN POR USUARIO (CLAVE PARA TU BLADE)
    // ---------------------------------------------------------

    $userIds = $orden->resultados->pluck('user_id')->filter()->unique();
    $usuarios = \App\Models\User::whereIn('id', $userIds)->get();

    if ($usuarios->isEmpty() && $orden->resultados->count() > 0) {
        $usuarios = collect([auth()->user()]);
    }

    $imgToBase64 = function ($path, int $maxWidth = 900, int $maxHeight = 900) {
        if ($path && file_exists($path)) {
            $realPath = realpath($path);

            if ($realPath === false) {
                return null;
            }

            if (!extension_loaded('gd')) {
                return str_replace('\\', '/', $realPath);
            }

            $size = @getimagesize($realPath);

            if ($size === false) {
                return str_replace('\\', '/', $realPath);
            }

            [$width, $height] = $size;

            if ($width <= $maxWidth && $height <= $maxHeight && filesize($realPath) <= 350 * 1024) {
                return str_replace('\\', '/', $realPath);
            }

            $cacheDir = storage_path('app/public/pdf-cache');

            if (!is_dir($cacheDir) && !mkdir($cacheDir, 0775, true) && !is_dir($cacheDir)) {
                return str_replace('\\', '/', $realPath);
            }

            $cachePath = $cacheDir . DIRECTORY_SEPARATOR . sha1($realPath . '|' . filemtime($realPath) . "|{$maxWidth}x{$maxHeight}") . '.png';

            if (file_exists($cachePath)) {
                return str_replace('\\', '/', realpath($cachePath));
            }

            $source = @imagecreatefromstring(file_get_contents($realPath));

            if (!$source) {
                return str_replace('\\', '/', $realPath);
            }

            $scale = min($maxWidth / $width, $maxHeight / $height, 1);
            $targetWidth = max(1, (int) round($width * $scale));
            $targetHeight = max(1, (int) round($height * $scale));
            $target = imagecreatetruecolor($targetWidth, $targetHeight);

            imagealphablending($target, false);
            imagesavealpha($target, true);
            $transparent = imagecolorallocatealpha($target, 255, 255, 255, 127);
            imagefilledrectangle($target, 0, 0, $targetWidth, $targetHeight, $transparent);
            imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
            $saved = imagepng($target, $cachePath, 6);
            imagedestroy($source);
            imagedestroy($target);

            $cacheRealPath = $saved ? realpath($cachePath) : false;

            return $cacheRealPath ? str_replace('\\', '/', $cacheRealPath) : str_replace('\\', '/', $realPath);
        }
        return null;
    };

    $grupos_finales = [];

    foreach ($usuarios as $usuario) {

        $uId = $usuario->id;
        $datos_usuario = [];

        foreach ($contenido_maestro as $tipo => $listaExamenes) {

            $exFiltrados = [];

            foreach ($listaExamenes as $ex) {

                $unitariasUser = array_filter(
                    $ex['pruebas_unitarias'],
                    fn($p) => ($p['user_id'] ?? null) == $uId
                );
                 $externosUser = array_filter(
        $ex['externos'] ?? [],
        fn($p) => ($p['user_id'] ?? null) == $uId
    );

                $matricesUser = [];

                foreach ($ex['matrices'] as $matriz) {

                    $tieneData = false;

                    foreach($matriz['data'] as $row) {
                        foreach($row as $cell) {
                            if (($cell['user_id'] ?? null) == $uId) {
                                $tieneData = true;
                                break 2;
                            }
                        }
                    }

                    if ($tieneData) {
                        $matricesUser[] = $matriz;
                    }
                }

                if (!empty($unitariasUser) || !empty($matricesUser) || !empty($externosUser)) {
    $exFiltrados[] = [
        'nombre' => $ex['nombre'],
        'pruebas_unitarias' => $unitariasUser,
        'matrices' => $matricesUser,
        'externos' => $externosUser,
    ];
}
            }

            if (!empty($exFiltrados)) {
                $datos_usuario[$tipo] = $exFiltrados;
            }
        }

        if (!empty($datos_usuario)) {

            $pathFirma = $usuario->firma_path
                ? storage_path('app/public/' . $usuario->firma_path)
                : null;

            $pathSello = $usuario->sello_path
                ? storage_path('app/public/' . $usuario->sello_path)
                : null;

            $grupos_finales[] = [
                'laboratorista' => $usuario->name,
                'firma_b64' => $imgToBase64($pathFirma, 420, 260),
                'sello_b64' => $imgToBase64($pathSello, 420, 260),
                'datos' => $datos_usuario
            ];
        }
    }

    // ---------------------------------------------------------
    // 3️⃣ GENERAR PDF
    // ---------------------------------------------------------

    $pdf_data = [
        'orden' => $orden,
        'grupos_por_usuario' => $grupos_finales,
        'logo_b64' => $imgToBase64(public_path(config('laboratorio.logo')), 260, 210),


        'sello_registro_b64' => null,
    ];

    $pdf = Pdf::setOptions([
        'isRemoteEnabled' => false,
        'isHtml5ParserEnabled' => true,
        'dpi' => 96,
        'defaultFont' => 'sans-serif',
        'chroot' => base_path(),
    ])->loadView('pdf.reporte_resultados', $pdf_data);

   $nombreCliente = Str::slug(
    $orden->cliente->nombre . ' ' . $orden->cliente->apellido
);

$fileName = strtoupper("{$nombreCliente} - {$orden->id} P.pdf");

    $pdfContent = $pdf->output();

    Storage::disk('public')->put("reportes/{$fileName}", $pdfContent);

    return response()->streamDownload(
        fn() => print($pdfContent),
        $fileName
    );
}

public function enviarPdfParcial()
{
    $record = $this->record;

    $labNombre = config('laboratorio.nombre');
    $labTelefonos = config('laboratorio.telefono');
    $labCorreo = config('laboratorio.correo');

    $nombreCliente = \Illuminate\Support\Str::slug(
        $record->cliente->nombre . ' ' . $record->cliente->apellido
    );

    $fileName = strtoupper("{$nombreCliente} - {$record->id} P.pdf");

    $filePath = "reportes/{$fileName}";
    $fullPath = storage_path("app/public/{$filePath}");

    if (!file_exists($fullPath)) {
        Notification::make()
            ->title('Debe generar el PDF parcial primero')
            ->danger()
            ->send();
        return;
    }

    $nombrePaciente = $record->cliente->nombre . ' ' . $record->cliente->apellido;

    $asuntoCorreo = "Resultados Parciales - Orden #{$record->id} - {$labNombre}";

    $mensajeBase = "Estimado(a) *{$nombrePaciente}*,\n\n";
    $mensajeBase .= "Le compartimos sus *resultados parciales de laboratorio*.\n\n";
    $mensajeBase .= "Por favor revise el documento PDF adjunto.\n\n";
    $mensajeBase .= "Para cualquier consulta estamos a su disposición en:\n";
    $mensajeBase .= "Teléfonos: {$labTelefonos}\n";
    $mensajeBase .= "Correo: {$labCorreo}\n\n";
    $mensajeBase .= "Gracias por confiar en nosotros.";

    $telefonoCliente = $record->cliente->telefono;
    $correoCliente = $record->cliente->correo;

    $linkWhatsapp = $telefonoCliente
        ? 'https://wa.me/' . $telefonoCliente . '?text=' . rawurlencode($mensajeBase)
        : null;

    $linkCorreo = $correoCliente
        ? 'mailto:' . $correoCliente .
            '?subject=' . rawurlencode($asuntoCorreo) .
            '&body=' . rawurlencode($mensajeBase)
        : null;

    Notification::make()
        ->title('PDF Parcial Descargado')
        ->body("El archivo se ha guardado en tu equipo.\nRecuerda adjuntar el PDF manualmente.")
        ->success()
        ->persistent()
        ->actions([
            \Filament\Notifications\Actions\Action::make('whatsapp')
                ->label('WhatsApp')
                ->url($linkWhatsapp, shouldOpenInNewTab: true)
                ->button()
                ->color('gray')
                ->visible(fn() => $linkWhatsapp !== null),

            \Filament\Notifications\Actions\Action::make('email')
                ->label('Correo')
                ->url($linkCorreo)
                ->button()
                ->color('gray')
                ->visible(fn() => $linkCorreo !== null),
        ])
        ->send();

    return response()->download($fullPath);
}
    protected function eliminarPdfParcial()
{
    $nombreCliente = \Illuminate\Support\Str::slug(
        $this->record->cliente->nombre . ' ' . $this->record->cliente->apellido
    );

    $fileName = strtoupper("{$nombreCliente} - {$this->record->id} P.pdf");

    Storage::disk('public')->delete("reportes/{$fileName}");
}
    
}
