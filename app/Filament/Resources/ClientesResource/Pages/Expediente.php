<?php

namespace App\Filament\Resources\ClientesResource\Pages;

use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;


use App\Filament\Resources\ClientesResource;
use Filament\Resources\Pages\Page;
use App\Models\Cliente;
use Illuminate\Support\Facades\Log;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use App\Filament\Pages\BuscarExpediente;
use Filament\Tables\Actions\Action; 
use Filament\Tables\Concerns\InteractsWithTable; 
use Filament\Tables\Contracts\HasTable;         
use Filament\Tables\Table;                      
use Filament\Tables\Columns\TextColumn;          
use App\Models\Orden;
use Filament\Forms\Components\ViewField; 
use Barryvdh\DomPDF\Facade\Pdf;       
use Illuminate\Support\Collection;
use Filament\Forms\Get;
use Storage;


class Expediente extends Page implements HasTable
{
    use InteractsWithTable; // 👈 USAR EL TRAIT
    protected static string $resource = ClientesResource::class;

    protected static string $view = 'filament.resources.clientes-resource.pages.expediente';

    public Cliente $record;

    public function mount($record): void
    {
        abort_unless(auth()->user()->can('ver_expediente_clientes'), 403);
        //dd($this->record);
        //$this->record = Cliente::findOrFail($record);

    }

    public function getTitle(): string
    {

        return ' ';
    }

    public function clienteInfolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($this->record)
            ->schema([
                Section::make('Información del Paciente')
                    ->schema([
                        Grid::make(4)->schema([
                            TextEntry::make('NumeroExp')->label('No. Expediente'),
                            TextEntry::make('dui')->label('DUI')->placeholder('No especificado'),
                            TextEntry::make('nombre')->label('Nombre Completo')
                                ->getStateUsing(fn($record) => $record->nombre . ' ' . $record->apellido),
                            TextEntry::make('genero')->label('Género'),
                        ]),

                        Grid::make(3)->schema([
                            TextEntry::make('telefonos_registrados')->label('Teléfonos')->listWithLineBreaks(),
                            TextEntry::make('correo'),
                            TextEntry::make('fecha_nacimiento')
                                ->label('Edad')
                                ->getStateUsing(fn($record) => \Carbon\Carbon::parse($record->fecha_nacimiento)->age . ' años'),
                        ]),

                        Grid::make(3)->schema([
                            TextEntry::make('direccion')->columnSpanFull(),
                        ]),
                    ])
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Orden::query()->with('cliente')->where('cliente_id', $this->record->id))
            ->columns([
                TextColumn::make('id')
                    ->label('# Orden')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('fecha')
                    ->date('d/m/Y')
                    ->label('Fecha')
                    ->sortable(),


                TextColumn::make('estado')
                    ->badge()
                    ->color(fn ($state) => \App\Support\EstadoVisual::color($state)),
            ])
            ->defaultSort('fecha', 'desc')
            ->emptyStateHeading('No se ha realizado ninguna orden')
            ->emptyStateDescription('Este paciente aún no tiene historial de órdenes registradas.')
            ->emptyStateIcon('heroicon-o-clipboard-document')
            ->actions([
                Action::make('ver_detalle_modal')
                    ->label('Ver Detalles')
                    ->icon('heroicon-o-eye')
                    ->iconButton()
                    ->visible(fn () => auth()->user()->can('ver_detalle_orden'))
                    ->color('gray')
                    ->modalHeading(fn(Orden $record) => 'Detalles de Orden #' . $record->id)
                    ->modalWidth('4xl')
                    ->modalContent(function (Orden $record) {
                        $record->load(['detalleOrden.examen.pruebas', 'resultados']);
                        return view('filament.modals.ver-orden', ['record' => $record]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),

                   Action::make('verReporteGuardado')
                    ->tooltip('Ver Reporte Guardado')
                    ->icon('heroicon-o-document-text')
                    ->iconButton()
                    ->color('success')
                    ->visible(fn (Orden $record): bool => $record->estado === 'finalizado' && $record->reporteGuardadoExists())
                    ->modalHeading(fn (Orden $record): string => "Reporte guardado de la orden #{$record->id}")
                    ->modalWidth('7xl')
                    ->modalContent(fn (Orden $record) => view('filament.modals.pdf-viewer', [
                        'pdfUrl' => route('orden.reporte.guardado', [
                            'orden' => $record,
                            'v' => filemtime($record->reporteGuardadoFullPath()) ?: time(),
                        ]),
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),

                   Action::make('descargarReporte')
                    ->tooltip('Descargar Reporte Guardado')
                    ->icon('heroicon-o-arrow-down-tray') // Icono de descarga
                    ->iconButton()
                    ->color('success') // Verde para diferenciar
                    // Solo visible si el archivo EXISTE en el disco
                    ->visible(function (Orden $record) {
                        return $record->estado === 'finalizado' && $record->reporteGuardadoExists();
                    })
                    ->action(function (Orden $record) {
                        return response()->download(
                            $record->reporteGuardadoFullPath(),
                            $record->reporteGuardadoFileName()
                        );
                    }),
                    
                    
                   Action::make('ver')

    ->tooltip('Ver Detalles')

    ->icon('heroicon-o-eye')

    ->iconButton()

    ->color('gray')

    ->modalContent(function (Orden $record) {

        // 1. Cargar relaciones base

        $record->load([

            'cliente',

            'tomaMuestraUser',

            'resultados', // Cargamos resultados para verificar el estado

            // Cargamos relaciones de respaldo por si es una orden vieja sin snapshot

            'detalleOrden.examen.pruebas.valoresReferencia', 

            'detalleOrden.perfil'

        ]);



        // 2. PROCESAR LA DATA (Lógica idéntica a IngresarResultados)

        // Generamos una estructura limpia para la vista que ya tenga las pruebas correctas

        $listaDePruebasVisual = $record->detalleOrden->map(function ($detalle) use ($record) {

            

            // A. Determinar la fuente de las pruebas (Snapshot vs BD)

            $pruebas = collect([]);

            

            if (!empty($detalle->pruebas_snapshot)) {

                // CASO 1: Usar Snapshot (JSON)

                $pruebas = collect($detalle->pruebas_snapshot)->map(fn($item) => (object) $item);

            } elseif ($detalle->examen) {

                // CASO 2: Fallback a BD viva (Ordenes viejas)

                $pruebas = $detalle->examen->pruebas;

            }



            // Si no hay pruebas (ej: examen externo sin configurar), retornamos null para no mostrarlo en la lista de estado

            if ($pruebas->isEmpty()) return null;



            // B. Mapear cada prueba con su estado de resultado

            $pruebasProcesadas = $pruebas->map(function ($prueba) use ($record, $detalle) {

                // Buscamos si ya tiene resultado

                $tieneResultado = $record->resultados

                    ->where('detalle_orden_id', $detalle->id)

                    ->where('prueba_id', $prueba->id)

                    ->whereNotNull('resultado')

                    ->where('resultado', '!=', '')

                    ->isNotEmpty();



                return [

                    'id' => $prueba->id,

                    'nombre' => $prueba->nombre,

                    'completado' => $tieneResultado,

                ];

            });



            return [

                'nombre_examen' => $detalle->nombre_examen ?? ($detalle->examen->nombre ?? 'Examen'),

                'es_externo' => $detalle->examen->es_externo ?? false,

                'pruebas' => $pruebasProcesadas,

            ];

        })->filter(); // Eliminar nulos



        // 3. Retornar la vista pasando la nueva variable procesada

        return view('filament.modals.ver-orden', [

            'record' => $record,

            'lista_pruebas_visual' => $listaDePruebasVisual // <--- VARIABLE NUEVA

        ]);

    })

    ->modalSubmitAction(false)

    ->modalCancelAction(false),

                 
            ]);
    }

  // En app/Filament/Resources/ClientesResource/Pages/Expediente.php

public static function getDatosPruebaParaPdf($prueba, $orden, $detalleId): array
{
    $resultado = $orden->resultados->where('prueba_id', $prueba->id)->where('detalle_orden_id', $detalleId)->first();

    $nombre_prueba = $prueba->nombre; 
    $referencia_formateada = 'N/A'; // Por defecto
    $unidades = '';                 // Por defecto
    $es_fuera_de_rango = false;
    $valor_resultado_num = null;

    if ($resultado && is_numeric($resultado->resultado)) {
        $valor_resultado_num = (float) $resultado->resultado;
    }

    $todosLosValores = collect([]);
    if ($prueba instanceof \App\Models\Prueba && $prueba->valoresReferencia->isNotEmpty()) {
        $todosLosValores = $prueba->valoresReferencia;
    } elseif (isset($prueba->valores_referencia)) {
        $todosLosValores = collect($prueba->valores_referencia);
    }

    // --- LÓGICA VIVA ---
    if ($todosLosValores->isNotEmpty()) {
        $cliente = $orden->cliente;
        $generoCliente = $cliente->genero; 
        $valorRef = null;

        // A. Embarazo
        if (isset($orden->semanas_gestacion) && $orden->semanas_gestacion) {
                $grupoEmbarazo = \App\Models\GrupoEtario::where('unidad_tiempo', 'semanas')
                ->where('edad_min', '<=', $orden->semanas_gestacion)
                ->where('edad_max', '>=', $orden->semanas_gestacion)
                ->first();

            if ($grupoEmbarazo) {
                $valorRef = $todosLosValores->first(fn($val) => ($val->grupo_etario_id == $grupoEmbarazo->id));
            }
        }

        // B. Grupos
        if (!$valorRef) {
            $grupoEtarioCliente = $cliente->getGrupoEtario(); 
            $grupoTodasEdades = \App\Models\GrupoEtario::where('nombre', 'Todas las edades')
                ->orWhere(fn($q) => $q->where('edad_min', 0)->where('edad_max', '>=', 120))
                ->first();

            if ($grupoEtarioCliente) {
                $valorRef = $todosLosValores->first(fn($val) => $val->grupo_etario_id == $grupoEtarioCliente->id && $val->genero == $generoCliente);
            }
            if (!$valorRef && $grupoEtarioCliente) {
                $valorRef = $todosLosValores->first(fn($val) => $val->grupo_etario_id == $grupoEtarioCliente->id && $val->genero == 'Ambos');
            }
            if (!$valorRef && $grupoTodasEdades) {
                $valorRef = $todosLosValores->first(fn($val) => $val->grupo_etario_id == $grupoTodasEdades->id && $val->genero == $generoCliente);
            }
            if (!$valorRef && $grupoTodasEdades) {
                $valorRef = $todosLosValores->first(fn($val) => $val->grupo_etario_id == $grupoTodasEdades->id && $val->genero == 'Ambos');
            }
        }

        if ($valorRef) {
            $valorMin = (float) $valorRef->valor_min;
            $valorMax = (float) $valorRef->valor_max;
            $unidades = $valorRef->unidades ?? '';

            $referencia_formateada = match ($valorRef->operador) {
                'rango' => "$valorMin - $valorMax",
                '<=' => "≤ $valorMax",
                '<' => "< $valorMax",
                '>=' => "≥ $valorMin",
                '>' => "> $valorMin",
                '=' => "= $valorMin",
                default => $valorRef->descriptivo ?? '',
            };
            
            // Fuera de rango
            if (!is_null($valor_resultado_num)) {
                switch ($valorRef->operador) {
                    case 'rango': if ($valor_resultado_num < $valorMin || $valor_resultado_num > $valorMax) $es_fuera_de_rango = true; break;
                    case '<=': if ($valor_resultado_num > $valorMax) $es_fuera_de_rango = true; break;
                    case '<': if ($valor_resultado_num >= $valorMax) $es_fuera_de_rango = true; break;
                    case '>=': if ($valor_resultado_num < $valorMin) $es_fuera_de_rango = true; break;
                    case '>': if ($valor_resultado_num <= $valorMin) $es_fuera_de_rango = true; break;
                    case '=': if ($valor_resultado_num != $valorMin) $es_fuera_de_rango = true; break;
                }
            }
        }
    }

    // --- SNAPSHOT ---
    if ($resultado && !empty($resultado->prueba_nombre_snapshot)) {
        $nombre_prueba = $resultado->prueba_nombre_snapshot;
        $referencia_formateada = $resultado->valor_referencia_snapshot ?? 'N/A';
        $unidades = $resultado->unidades_snapshot ?? '';
    }

    // --- LIMPIEZA FINAL (ANTI "SIN RANGO") ---
    if ($referencia_formateada === 'SIN RANGO' || empty($referencia_formateada)) {
        $referencia_formateada = 'N/A';
        $unidades = '';
    }

    return [
        'nombre' => $nombre_prueba,
        'resultado' => $resultado->resultado ?? 'PENDIENTE',
        'referencia' => $referencia_formateada,
        'unidades' => $unidades,
        'fecha_resultado' => $resultado ? $resultado->updated_at->format('d/m/Y') : '',
        'es_fuera_de_rango' => $es_fuera_de_rango,
        'tipo_prueba' => $prueba->tipoPrueba->nombre ?? '',
    ];
}
protected function getHeaderActions(): array
    {
        return [
            // Usamos la barra invertida \ para decirle a PHP que busque la clase exacta
            // Esta es la acción de PÁGINA (Header)
            \Filament\Actions\Action::make('regresar_buscar') 
                ->label('Buscar Otro Paciente')
                ->visible(fn() => auth()->user()->can('acceder_buscador_expedientes'))
                ->url(\App\Filament\Pages\BuscarExpediente::getUrl())
                ->icon('heroicon-o-magnifying-glass')
                ->color('gray'),

            \Filament\Actions\Action::make('regresar a clientes') 
                ->label('Lista de Clientes')
                ->visible(fn() => auth()->user()->can('view_any_clientes'))
                ->url($this->getResource()::getUrl('index'))
                ->icon('heroicon-o-users')
                ->color('gray'),
        ];
    }
   
}
