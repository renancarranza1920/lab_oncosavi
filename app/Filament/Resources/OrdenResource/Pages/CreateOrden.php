<?php

namespace App\Filament\Resources\OrdenResource\Pages;

use App\Filament\Pages\DetalleOrdenKanban;
use App\Filament\Resources\OrdenResource;
use App\Models\cliente;
use App\Models\Examen;
use App\Models\Codigo;
use App\Models\Perfil;
use App\Services\OrdenSelectionService;
use Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Filament\Forms\Components\Wizard\Step;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Number;
use Illuminate\Support\HtmlString;
use Filament\Notifications\Actions\Action as NotificationAction;

class CreateOrden extends CreateRecord
{
    use HasWizard;

    protected static string $resource = OrdenResource::class;

    // Propiedades para manejar el estado del descuento en vivo
    public float $subtotal = 0;
    public float $descuento = 0;
    public ?Codigo $codigoAplicado = null;

    protected function getSteps(): array
    {
        return [
            Step::make('Cliente')
                ->schema(OrdenResource::getClienteStep()),

            Step::make('Orden')
                ->schema(OrdenResource::getOrdenStep()),

            Step::make('Resumen')
                ->schema(fn(Get $get): array => [
                    // 1. SECCIÓN DE CUPÓN MOVIDA AQUÍ
                    OrdenResource::getCuponSection(),

                    // 2. SECCIÓN DE RESUMEN FINANCIERO
                    Placeholder::make('resumen_costos')
                        ->label('Resumen Financiero')
                        ->content(function (Get $get) {
                            $this->calcularSubtotal($get);
                            $total = $this->subtotal - $this->descuento;

                            $html = "<div class='text-base space-y-1'>";
                            $html .= "<div><strong>Subtotal:</strong> <span class='float-right'>" . Number::currency($this->subtotal, 'USD') . "</span></div>";

                            if ($this->descuento > 0 && $this->codigoAplicado) {
                                $html .= "<div class='text-success-600'><strong>Descuento ({$this->codigoAplicado->codigo}):</strong> <span class='float-right'>- " . Number::currency($this->descuento, 'USD') . "</span></div>";
                            }

                            $html .= "<div class='text-lg font-bold border-t border-gray-300 pt-1 mt-1'><strong>Total a Pagar:</strong> <span class='float-right text-primary-600'>" . Number::currency($total, 'USD') . "</span></div>";
                            $html .= "</div>";

                            return new HtmlString($html);
                        })->columnSpanFull(),

                    // 3. SECCIÓN DE DETALLES (LISTA DE EXÁMENES)
                  // ... dentro de getSteps() -> Step 'Resumen'

\Filament\Forms\Components\View::make('filament.forms.components.resumen-orden')
    ->label('Resumen de la Orden')
    // 1. EL TRUCO: key() fuerza a repintar el componente si cambia el subtotal o el descuento
    ->key('view-resumen-' . $this->subtotal . '-' . $this->descuento)
    ->viewData([
        'cliente' => \App\Models\Cliente::find($get('cliente_id')),
        'perfilesSeleccionados' => OrdenSelectionService::perfilesUnicos($get('perfiles_seleccionados') ?? []),
        'examenesSeleccionados' => OrdenSelectionService::examenesCobrables(
            $get('perfiles_seleccionados') ?? [],
            $get('examenes_seleccionados') ?? []
        ),
        
        // 2. LA CORRECCIÓN: Usa $this-> propiedad, NO $get()
        'codigoAplicado' => $this->codigoAplicado, 
        'descuento'      => $this->descuento,      
        'subtotal'       => $this->subtotal,       
    

           // dd( Cliente::find($get('cliente_id')))
    ])


                ]),
        ];
    }

    // --- LÓGICA DE NEGOCIO (CALCULAR, LIMPIAR, APLICAR) ---

    protected function calcularSubtotal(callable $dataSource): void
    {
        $perfiles = $dataSource('perfiles_seleccionados') ?? [];
        $examenes = $dataSource('examenes_seleccionados') ?? [];

        $this->subtotal = OrdenSelectionService::subtotal($perfiles, $examenes);
    }

    public function limpiarDescuento(): void
    {
        $this->descuento = 0;
        $this->codigoAplicado = null;
        // No limpiamos el input ($set) para no borrar lo que el usuario está escribiendo.
    }

    public function aplicarCodigo(?Get $get = null, ?Set $set = null): void
    {
        // Si no nos pasaron $get (llamada directa desde acción sin parámetros),
        // obtenemos el estado desde $this->form->getState()
        $state = $get ? null : $this->form->getState();

        // Reiniciamos y recalculamos
        $this->limpiarDescuento();

        if ($get) {
            // Modo normal (desde callback que pasa Get/Set)
            $this->calcularSubtotal($get);
            $codigoStr = $get('codigo_input');
        } else {
            // Modo defensivo (sin Get): usamos el estado obtenido previamente
            $this->calcularSubtotal(fn($key) => $state[$key] ?? null);
            $codigoStr = $state['codigo_input'] ?? null;
        }

        if (empty($codigoStr)) {
            Notification::make()->title('Por favor, ingresa un código.')->warning()->send();
            return;
        }

        $codigo = Codigo::where('codigo', $codigoStr)->first();

        if (!$codigo) {
            Notification::make()->title('Código no encontrado')->danger()->send();
            return;
        }

        if ($codigo->codigo !== $codigoStr) {
         Notification::make()
            ->title('Código inválido')
            ->body('Verifica las mayúsculas y minúsculas exactamente.')
            ->danger()
            ->send();
        return;
    }

        if (!$codigo->esValido()) {
            Notification::make()->title('Código no válido')->body('Expirado o límite alcanzado.')->danger()->send();
            return;
        }

        $nuevoTotal = $codigo->aplicarDescuento($this->subtotal);
        $this->descuento = $this->subtotal - $nuevoTotal;
        $this->codigoAplicado = $codigo;

        Notification::make()->title('¡Cupón aplicado!')->body('Descuento: ' . Number::currency($this->descuento, 'USD'))->success()->send();

        // Si nos pasaron $set y quieres actualizar el campo visualmente, puedes hacerlo:
        if ($set) {
            // opcional: dejar el input con el código aplicado (o vaciarlo)
            $set('codigo_input', $codigo->codigo);
        }
    }


    // --- MANEJO DE REGISTRO Y GUARDADO ---

protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
{
    return DB::transaction(function () use ($data) {
        $orden = static::getModel()::create($data);
        $this->record = $orden;

        if ($this->codigoAplicado) {
            $this->codigoAplicado->registrarUso();
        }

        session()->flash('from_create_orden', true);
        $state = $this->form->getState();
        $perfiles = OrdenSelectionService::perfilesUnicos($state['perfiles_seleccionados'] ?? []);
        $examenes = OrdenSelectionService::examenesCobrables(
            $state['perfiles_seleccionados'] ?? [],
            $state['examenes_seleccionados'] ?? []
        );
        
        // --- VARIABLE PARA CONTROLAR DUPLICADOS ---
        $examenesProcesados = [];

        // --- FUNCIÓN HELPER PARA GENERAR EL SNAPSHOT PROFUNDO ---
        $generarSnapshot = function($examenId) {
            $examenModel = Examen::with([
                'pruebas.valoresReferencia' 
            ])->find($examenId);

            if (!$examenModel) return null;

            return $examenModel->pruebas->where('estado', 'activo')->map(function($prueba) {
                $data = [
                    'id' => $prueba->id,
                    'nombre' => $prueba->nombre,
                    'tipo_conjunto' => $prueba->tipo_conjunto,
                    'tipo_prueba_id' => $prueba->tipo_prueba_id,
                    'valores_referencia' => $prueba->valoresReferencia
                        ->map(function($val) {
                            return [
                                'grupo_etario_id' => $val->grupo_etario_id,
                                'genero' => $val->genero,
                                'valor_min' => $val->valor_min,
                                'valor_max' => $val->valor_max,
                                'operador' => $val->operador,
                                'unidades' => $val->unidades,
                                'descriptivo' => $val->descriptivo,
                                'nota' => $val->nota,
                            ];
                        })->values()->toArray(),
                ];
                return $data;
            })->toArray();
        };

        // 1. PROCESAR PERFILES
        foreach ($perfiles as $perfil) {
            $perfilModel = Perfil::find($perfil['perfil_id']);
            $nombrePerfil = $perfilModel?->nombre ?? 'Perfil desconocido';
            $precioPerfil = $perfil['precio_hidden'];

            if ($perfilModel) {
                $perfilModel->examenes()
                    ->where('estado', 1)
                    ->get()
                    ->each(function ($examen) use ($orden, $generarSnapshot, $nombrePerfil, $precioPerfil, &$examenesProcesados) {
                        
                        // VALIDACIÓN: Si el examen ya se procesó, no lo creamos de nuevo
                        if (in_array($examen->id, $examenesProcesados)) {
                            return; 
                        }

                        $orden->detalleOrden()->create([
                            'examen_id' => $examen->id,
                            'perfil_id' => $examen->pivot->perfil_id,
                            'nombre_perfil' => $nombrePerfil,
                            'precio_perfil' => $precioPerfil,
                            'nombre_examen' => $examen->nombre,
                            'precio_examen' => $examen->precio,
                            'status' => $examen->recipiente,
                            'pruebas_snapshot' => $generarSnapshot($examen->id),
                        ]);

                        // Marcamos como procesado
                        $examenesProcesados[] = $examen->id;
                    });
            }
        }

        // 2. PROCESAR EXÁMENES INDIVIDUALES
        foreach ($examenes as $examen) {
            $examenId = $examen['examen_id'];

            // VALIDACIÓN: Si el examen ya vino en un perfil, lo ignoramos aquí
            if (in_array($examenId, $examenesProcesados)) {
                continue;
            }

            $orden->detalleOrden()->create([
                'examen_id' => $examenId,
                'nombre_examen' => $examen['nombre_examen'],
                'precio_examen' => $examen['precio_hidden'],
                'status' => $examen['recipiente'] ?? null,
                'pruebas_snapshot' => $generarSnapshot($examenId),
            ]);

            $examenesProcesados[] = $examenId;
        }

        return $orden;
    });
}

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $state = $this->form->getState();
        $this->calcularSubtotal(fn($path) => $state[$path] ?? null);

        $data['total'] = $this->subtotal - $this->descuento;
        $data['descuento'] = $this->descuento;
        $data['codigo_id'] = $this->codigoAplicado?->id;

        $data['fecha'] = Carbon::now();
        $data['estado'] = 'pendiente';

        return $data;
    }

    protected function beforeCreate(): void
    {
        $state = $this->form->getState();
        $perfiles = $state['perfiles_seleccionados'] ?? [];
        $examenes = $state['examenes_seleccionados'] ?? [];

        if (empty($perfiles) && empty($examenes)) {
            Notification::make()
                ->title('Debe seleccionar al menos un perfil o un examen.')
                ->danger()
                ->persistent()
                ->send();
            throw new Halt();
        }
    }

// Mientras no haya etiquetadora, regresar al listado sin eliminar el flujo del Kanban.
    protected function getRedirectUrl(): string
    {
        if (! config('laboratorio.impresion_etiquetas_habilitada')) {
            return static::getResource()::getUrl('index');
        }

        // Al terminar el Wizard, lo mandamos al index o al Kanban 
        // pero la notificación le dará la opción de imprimir.
        return DetalleOrdenKanban::getUrl(['ordenId' => $this->record->id]);
    }

    // 2. Creamos la notificación personalizada con el botón de impresión
protected function getCreatedNotificationTitle(): ?string
{
    return null; // elimina mensaje default Filament
}

protected function getCreatedNotification(): ?Notification
{
    return Notification::make()
        ->success()
        ->title('¡Orden Registrada!')
        ->body('Presione para imprimir la boleta (Copia Cliente/Laboratorio)')
        ->duration(1000)
        ->actions([
            NotificationAction::make('imprimir')
                ->label('Imprimir Boleta')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(route('orden.boleta.pdf', ['orden' => $this->record->id]))
                ->openUrlInNewTab(),
        ]);
}


    public function generatePdfPreview(): StreamedResponse
    {
        // 1. Recolectar el estado actual del formulario
        $state = $this->form->getState();

        $cliente = Cliente::find($state['cliente_id'] ?? null);
        $perfilesSeleccionados = OrdenSelectionService::perfilesUnicos($state['perfiles_seleccionados'] ?? []);
        $examenesSeleccionados = OrdenSelectionService::examenesCobrables(
            $state['perfiles_seleccionados'] ?? [],
            $state['examenes_seleccionados'] ?? []
        );


        // --- ¡LÓGICA DE TOTAL ACTUALIZADA! ---
        $this->calcularSubtotal(fn($path) => $state[$path] ?? null);
        $total = $this->subtotal - $this->descuento;
        // --- FIN ---

        // ---> ¡NUEVO! Obtenemos el usuario autenticado
        $usuarioNombre = Auth::user() ? Auth::user()->name : 'N/A';
        $dataPerfiles = [];
        foreach ($perfilesSeleccionados as $item) {
            $perfil = Perfil::with('examenes')->find($item['perfil_id']);
            if ($perfil) {
                $precio = floatval($item['precio_hidden'] ?? $perfil->precio);
                $dataPerfiles[] = ['nombre' => $perfil->nombre, 'precio' => $precio, 'examenes' => $perfil->examenes];
                // El total ya se calcula arriba
            }
        }

        $dataExamenes = [];
        foreach ($examenesSeleccionados as $item) {
            $examen = Examen::find($item['examen_id']);
            if ($examen) {
                $precio = floatval($item['precio_hidden'] ?? $examen->precio);
                $dataExamenes[] = ['nombre' => $examen->nombre, 'precio' => $precio];
                // El total ya se calcula arriba
            }
        }

        // 2. Preparar datos y generar el PDF
        $data = [
            'cliente' => $cliente,
            'perfiles' => $dataPerfiles,
            'examenes' => $dataExamenes,
            'total' => $total, // <-- Total con descuento
            'subtotal' => $this->subtotal, // <-- Subtotal
            'descuento' => $this->descuento, // <-- Descuento
            'codigo' => $this->codigoAplicado?->codigo, // <-- Código
            'usuario_nombre' => $usuarioNombre,
        ];

    $pdf = Pdf::loadView('pdf.comprobante', $data)->setPaper('letter', 'portrait');

    // 3. Enviar el PDF al navegador para descarga
    return new StreamedResponse(function () use ($pdf) {
        echo $pdf->output();
    }, 200, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'attachment; filename="comprobante-preliminar.pdf"',
    ]);
    }


}
