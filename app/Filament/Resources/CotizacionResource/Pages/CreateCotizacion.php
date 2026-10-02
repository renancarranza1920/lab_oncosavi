<?php

namespace App\Filament\Resources\CotizacionResource\Pages;

use App\Filament\Resources\OrdenResource;
use App\Models\Examen;
use App\Models\Perfil;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Get;
use Filament\Resources\Pages\Page as ResourcePage;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class CreateCotizacion extends ResourcePage implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = \App\Filament\Resources\CotizacionResource::class;
    protected static string $view = 'filament.pages.create-cotizacion-page';
    
    protected static ?string $title = 'Crear Cotización';

    public ?array $data = [];

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('access_cotizaciones'), 403);
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make($this->getSteps())
                    ->submitAction(new HtmlString('<button type="submit" class="hidden"></button>')),
            ])
            ->statePath('data');
    }

    protected function getSteps(): array
    {
        return [
            Step::make('Datos del Cliente')
    ->schema([
        // 1. NOMBRE COMPLETO (Opcional)
        TextInput::make('nombre_completo')
            ->label('Nombre Completo del Cliente')
            ->placeholder('Ingrese el nombre completo del cliente')
            ->maxLength(255)
            ->nullable(), // <--- Coma normal, NO cierres el esquema aquí

        // 2. WHATSAPP (Opcional)
        \App\Support\TelefonoCliente::campo('whatsapp', 'Número de WhatsApp'),

        // 3. EMAIL (Opcional)
        TextInput::make('email')
            ->label('Correo Electrónico (Opcional)')
            ->email()
            ->live(onBlur: true)
            ->helperText('Ingrese el correo electrónico del cliente si desea enviar una copia.')
            ->nullable()
            ->validationMessages([
                'email' => 'Ingrese un correo electrónico válido.',
            ]),
    ]), // <--- AQUÍ SÍ se cierra el esquema y el Step

            Step::make('Selección de Estudios')
                ->schema(OrdenResource::getOrdenStep()),

            Step::make('Resumen')
                ->schema(fn(Get $get): array => [
                    \Filament\Forms\Components\View::make('resumen_detallado')
                        ->view('filament.forms.components.resumen-cotizacion')
                        ->viewData([
                            'nombre_cliente' => $get('nombre_completo'),
                            'perfilesSeleccionados' => $get('perfiles_seleccionados') ?? [],
                            'examenesSeleccionados' => $get('examenes_seleccionados') ?? [],
                        ]),
                ]),

            Step::make('Enviar y Descargar')
                ->schema([
                    Placeholder::make('acciones_finales')
                        ->label('Acciones')
                        ->content('Descargue el PDF y comparta el resumen por WhatsApp o Gmail. Para enviar el archivo, adjúntelo en la conversación o el correo antes de enviarlo.')
                        ->columnSpanFull(),

                    \Filament\Forms\Components\Actions::make([
                        FormAction::make('generarPdf')
                            ->label('Generar PDF')
                            ->visible(fn () => auth()->user()->can('generar_pdf_cotizacion'))
                            ->icon('heroicon-o-document-arrow-down')
                            ->action(fn() => $this->generatePdfPreview(true)),

                        FormAction::make('enviarWhatsApp')
                            ->label('Compartir por WhatsApp')
                            ->visible(fn () => auth()->user()->can('enviar_cotizacion_whatsapp'))
                            ->icon('heroicon-o-paper-airplane')
                            ->color('gray')
                            ->url(fn (Get $get) => $this->getWhatsAppUrl($get))
                            ->openUrlInNewTab()
                            ->disabled(fn (Get $get) => blank($get('whatsapp'))),

                        FormAction::make('enviarEmail')
                            ->label('Compartir por Gmail')
                            ->visible(fn () => auth()->user()->can('enviar_cotizacion_email'))
                            ->icon('heroicon-o-envelope')
                            ->color('gray')
                            ->url(fn (Get $get) => $this->getEmailUrl($get))
                            ->openUrlInNewTab()
                            ->disabled(fn (Get $get) => blank($get('email'))),
                    ])->columnSpanFull(),
                ]),
        ];
    }

    public function generatePdfPreview(bool $download = true)
{
    abort_unless(auth()->user()?->can('generar_pdf_cotizacion'), 403);
    $state = $this->form->getState();
    $total = 0;
    $dataPerfiles = [];

    foreach ($state['perfiles_seleccionados'] ?? [] as $item) {
        
        // --- AQUÍ ESTÁ EL CAMBIO ---
        // En lugar de Perfil::with('examenes'), usamos el array con la función anónima
        // para filtrar solo los activos (estado = 1)
        $perfil = Perfil::with(['examenes' => function ($query) {
            $query->where('estado', 1);
        }])->find($item['perfil_id']);

        if ($perfil) {
            $precio = floatval($item['precio_hidden'] ?? $perfil->precio);
            
            // $perfil->examenes ya viene filtrado desde la base de datos
            $dataPerfiles[] = [
                'nombre' => $perfil->nombre, 
                'precio' => $precio, 
                'examenes' => $perfil->examenes
            ];
            
            $total += $precio;
        }
    }

    $dataExamenes = [];
    foreach ($state['examenes_seleccionados'] ?? [] as $item) {
        $examen = Examen::find($item['examen_id']);
        
        // Opcional: También puedes validar aquí si el examen suelto sigue activo
        // if ($examen && $examen->estado == 1) { ... }

        if ($examen) {
            $precio = floatval($item['precio_hidden'] ?? $examen->precio);
            $dataExamenes[] = ['nombre' => $examen->nombre, 'precio' => $precio];
            $total += $precio;
        }
    }

    $data = [
        'cliente_nombre' => $state['nombre_completo'] ?? 'N/A',
        'perfiles' => $dataPerfiles,
        'examenes' => $dataExamenes,
        'total' => $total,
        'usuario_nombre' => Auth::user()?->name ?? 'N/A',
    ];

    $pdf = Pdf::loadView('pdf.cotizacion', $data)->setPaper('letter', 'portrait');

    if ($download) {
        return response()->streamDownload(fn() => print ($pdf->output()), 'cotizacion-' . date('Y-m-d') . '.pdf', [
            'Content-Type' => 'application/pdf',
        ]);
    } else {
        $nombreArchivo = 'cotizaciones/cotizacion-' . uniqid() . '.pdf';
        Storage::disk('public')->put($nombreArchivo, $pdf->output());
        return asset('storage/' . $nombreArchivo);
    }
}

    protected function getWhatsAppUrl(Get $get): ?string
    {
        $numero = \App\Support\TelefonoCliente::internacional($get('whatsapp'), $get('whatsapp_codigo_pais'));
        if (!$numero) {
            return null;
        }

        $mensaje = "¡Hola {$get('nombre_completo')}!\n\n" .
            "Le saluda *" . config('laboratorio.nombre') . "*.\n\n" .
            "Compartimos el resumen de su cotización:\n\n" .
            $this->getTextSummary($get) . "\n\n" .
            "Gracias por confiar en nosotros.\n" .
            config('laboratorio.telefono') . ' · ' . config('laboratorio.correo');

        return "https://wa.me/{$numero}?text=" . rawurlencode($mensaje);
    }

    protected function getEmailUrl(Get $get): ?string
    {
        if (blank($get('email'))) {
            return null;
        }

        $mensaje = "Hola {$get('nombre_completo')},\n\n" .
            "Gracias por solicitar una cotización. Aquí tiene el resumen:\n\n" .
            $this->getTextSummary($get) . "\n\nAtentamente,\n" .
            (Auth::user()?->name ?? config('laboratorio.nombre')) . "\n" .
            config('laboratorio.nombre') . "\n" .
            config('laboratorio.telefono') . ' · ' . config('laboratorio.correo');

        return 'https://mail.google.com/mail/?' . http_build_query([
            'view' => 'cm', 'fs' => '1', 'to' => $get('email'),
            'su' => 'Cotización de Servicios - ' . config('laboratorio.nombre'),
            'body' => $mensaje,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    protected function getTextSummary(Get $get): string
    {
        $total = 0;
        $lines = [];
        foreach ($get('perfiles_seleccionados') ?? [] as $item) {
            $perfil = Perfil::find($item['perfil_id']);
            if ($perfil) {
                $precio = floatval($item['precio_hidden'] ?? $perfil->precio);
                $lines[] = "*{$perfil->nombre}* - $" . number_format($precio, 2);
                $total += $precio;
            }
        }
        foreach ($get('examenes_seleccionados') ?? [] as $item) {
            $examen = Examen::find($item['examen_id']);
            if ($examen) {
                $precio = floatval($item['precio_hidden'] ?? $examen->precio);
                $lines[] = "*- {$examen->nombre}* - $" . number_format($precio, 2);
                $total += $precio;
            }
        }
        $lines[] = "\n*Total a Pagar:* $" . number_format($total, 2);
        return implode("\n", $lines);
    }
}
