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
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page as ResourcePage;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CreateCotizacion extends ResourcePage implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = \App\Filament\Resources\CotizacionResource::class;
    protected static string $view = 'filament.pages.create-cotizacion-page';
    
    protected static ?string $title = 'Crear Cotización';

    public ?array $data = [];

    public function mount(): void
    {
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
        TextInput::make('whatsapp')
            ->label('Número de WhatsApp')
            ->tel()
            ->nullable() // Permite vacío
            ->prefix('+503')
            ->mask('9999-9999')
            ->helperText('Opcional. Ingresar solo si el cliente lo proporciona.')
            ->rule('min:8') // Solo valida si escriben algo
            ->validationMessages([
                'min' => 'Si ingresa un número, debe tener al menos 8 dígitos.',
            ]), // <--- Coma normal

        // 3. EMAIL (Opcional)
        TextInput::make('email')
            ->label('Correo Electrónico (Opcional)')
            ->email() // Esto ya valida que sea formato email
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
                        ->content('Utilice los siguientes botones para descargar la cotización o compartirla.')
                        ->columnSpanFull(),

                    \Filament\Forms\Components\Actions::make([
                        FormAction::make('generarPdf')
                            ->label('Generar PDF')
                            ->icon('heroicon-o-document-arrow-down')
                            ->action(fn() => $this->generatePdfPreview(true)),

                        FormAction::make('enviarWhatsApp')
                            ->label('WhatsApp y Descargar PDF')
                            ->icon('heroicon-o-paper-airplane')
                            ->color('gray')
                            ->action(function (Get $get) {
                                $numero = '503' . preg_replace('/[^0-9]/', '', $get('whatsapp'));
                                $mensaje = urlencode(
                                    "¡Hola {$get('nombre_completo')}!\n\n" .
                                    "Le saluda con gusto *" . config('laboratorio.nombre') . "*. \n\n" .
                                    "Hemos preparado el resumen de su cotización y queremos compartirlo con usted:\n\n" .
                                    $this->getTextSummary($get) . "\n\n" .
                                    "Gracias por confiar en nosotros, estamos para servirle.\n" .
                                    config('laboratorio.telefono') . ' · ' . config('laboratorio.correo')
                                );
                                $whatsappUrl = "https://wa.me/{$numero}?text={$mensaje}";
                                $this->dispatch('open-url-in-new-tab', url: $whatsappUrl);
                                return $this->generatePdfPreview(true);
                            }),

                       
                        FormAction::make('enviarEmail')
                            ->label('Gmail y Descargar PDF')
                            ->icon('heroicon-o-envelope')
                            ->color('gray')
                            ->action(function (Get $get) {
                                $email = $get('email');
                                if (empty($email)) {
                                    Notification::make()
                                        ->title('Correo no especificado')
                                        ->body('Por favor, ingrese un correo en el primer paso para usar esta función.')
                                        ->warning()
                                        ->send();
                                    return; // Se usa 'return' para detener la acción
                                }
                                $subject = "Cotización de Servicios - " . config('laboratorio.nombre');
                                $body = "Hola {$get('nombre_completo')},\n\n" .
                                    "Gracias por solicitar una cotización con nosotros. Aquí tiene un resumen:\n\n" .
                                    $this->getTextSummary($get) . "\n\n" .
                                    "Quedamos a su entera disposición para cualquier consulta.\n\n" .
                                    "Atentamente,\n" .
                                    (Auth::user()?->name ?? config('laboratorio.nombre')) . "\n" .
                                    config('laboratorio.nombre') . "\n" .
                                    config('laboratorio.telefono') . ' · ' . config('laboratorio.correo');

                                $gmailUrl = "https://mail.google.com/mail/?view=cm&fs=1&to=" . rawurlencode($email) . "&su=" . rawurlencode($subject) . "&body=" . rawurlencode($body);

                                // 1. Envía el evento para abrir Gmail
                                $this->dispatch('open-url-in-new-tab', url: $gmailUrl);

                                // 2. Devuelve la descarga del PDF
                                return $this->generatePdfPreview(true);
                            }),
                    ])->columnSpanFull(),
                ]),
        ];
    }

    public function generatePdfPreview(bool $download = true)
{
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
        return response()->streamDownload(fn() => print ($pdf->stream()), 'cotizacion-' . date('Y-m-d') . '.pdf');
    } else {
        $nombreArchivo = 'cotizaciones/cotizacion-' . uniqid() . '.pdf';
        Storage::disk('public')->put($nombreArchivo, $pdf->output());
        return asset('storage/' . $nombreArchivo);
    }
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

