<?php

namespace App\Filament\Pages;

use App\Models\EnvioWhatsApp;
use App\Services\WhatsAppService;
use App\Support\AvisoWhatsApp;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class EnviosWhatsApp extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'Atención al Paciente';

    protected static ?string $navigationLabel = 'Envíos WhatsApp';

    protected static ?string $title = 'Envíos WhatsApp';

    protected static string $view = 'filament.pages.envios-whatsapp';

    public string $estadoConexion = 'sin consultar';

    public ?string $qr = null;

    public string $mensajeConexion = 'Consultando el servicio de WhatsApp…';

    public static function canAccess(): bool
    {
        return auth()->user()?->canAny(['enviar_cotizacion_whatsapp', 'enviar_reporte_orden', 'manage_settings']) ?? false;
    }

    protected function getHeaderActions(): array
    {
        return [Action::make('vincular')->label('Vincular WhatsApp')->icon('heroicon-o-qr-code')
            ->visible(fn () => auth()->user()?->can('manage_settings'))->action(fn () => $this->consultarConexion(true))];
    }

    public function consultarConexion(bool $iniciar = false): void
    {
        abort_unless(auth()->user()?->can('manage_settings'), 403);
        try {
            $datos = app(WhatsAppService::class)->conexion($iniciar ? 'connect' : 'status');
            $this->estadoConexion = match ($datos['status']) {
                'connected' => 'conectado', 'qr' => 'pendiente de escanear el QR',
                'connecting' => 'conectando', default => 'desconectado',
            };
            $this->qr = $datos['qr'];
            $this->mensajeConexion = match ($datos['code'] ?? null) {
                'auth_expired' => 'El teléfono cerró esta sesión. Pulse Vincular WhatsApp para obtener un QR nuevo.',
                'connection_replaced' => 'Este WhatsApp se conectó en otro servicio. Cierre esa conexión antes de volver a vincularlo.',
                'protocol_error' => 'WhatsApp rechazó la conexión. El administrador debe revisar la compatibilidad del servicio.',
                'qr_expired' => 'El QR venció. Pulse Vincular WhatsApp para obtener otro.',
                'network_error', 'connection_timeout' => 'No se pudo establecer conexión con WhatsApp. Revise la conexión a Internet del servidor y vuelva a intentarlo.',
                'session_error' => 'No se pudo preparar la sesión de WhatsApp. Pida al administrador que revise el servicio.',
                'qr_error' => 'No se pudo preparar el QR. Vuelva a intentarlo.',
                default => match ($datos['status']) {
                    'connected' => 'WhatsApp está vinculado y listo para enviar PDFs.',
                    'qr' => 'Escanee este QR desde Dispositivos vinculados en el teléfono del laboratorio.',
                    'connecting' => 'Conectando con WhatsApp. El QR aparecerá aquí automáticamente; puede tardar hasta 30 segundos.',
                    default => 'Pulse Vincular WhatsApp para comenzar.',
                },
            };
        } catch (\DomainException $e) {
            $this->qr = null;
            $this->estadoConexion = 'no disponible';
            $this->mensajeConexion = $e->getMessage();
            if ($iniciar) {
                Notification::make()->title('WhatsApp no disponible')->body($e->getMessage())->warning()->send();
            }
        }
    }

    public function actualizarConexion(): void
    {
        abort_unless(auth()->user()?->can('manage_settings'), 403);
        if ($this->estadoConexion !== 'no disponible') {
            $this->consultarConexion();
        }
    }

    public function table(Table $table): Table
    {
        return $table->query(EnvioWhatsApp::query()->when(! auth()->user()?->can('manage_settings'), fn ($q) => $q->where('user_id', auth()->id())))
            ->defaultSort('created_at', 'desc')->poll('10s')->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Solicitado')->dateTime('d/m/Y H:i'),
                Tables\Columns\TextColumn::make('orden_id')->label('Orden')->placeholder('Cotización'),
                Tables\Columns\TextColumn::make('tipo')->label('Documento')->formatStateUsing(fn ($state) => match ($state) {
                    'reporte_final' => 'Resultados', 'reporte_parcial' => 'Resultados parciales', default => 'Cotización',
                }),
                Tables\Columns\TextColumn::make('telefono')->label('Destino'),
                Tables\Columns\TextColumn::make('estado')->label('Estado')->badge()->color(fn ($state) => match ($state) {
                    'enviado' => 'success', 'fallido' => 'danger', default => 'warning',
                })->tooltip(fn (EnvioWhatsApp $record) => $record->mensajeEstado()),
            ])->actions([
                Tables\Actions\Action::make('actualizar')->label('Consultar estado')->icon('heroicon-o-arrow-path')
                    ->visible(fn (EnvioWhatsApp $record) => in_array($record->estado, ['enviando', 'desconocido'], true))
                    ->action(fn (EnvioWhatsApp $record) => AvisoWhatsApp::enviar(fn () => app(WhatsAppService::class)->actualizar($record))),
            ])->emptyStateHeading('Todavía no hay envíos por WhatsApp')->emptyStateDescription('Envíe un PDF desde Órdenes o Cotizaciones.');
    }
}
