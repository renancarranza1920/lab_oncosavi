<?php

namespace App\Services;

use App\Models\EnvioWhatsApp;
use App\Models\Orden;
use App\Support\RegistroWhatsApp;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WhatsAppService
{
    public function orden(Orden $orden, bool $parcial = false): EnvioWhatsApp
    {
        abort_unless(auth()->user()?->can('view_orden') && auth()->user()?->can('enviar_reporte_orden'), 403);
        if (! $parcial && $orden->estado !== 'finalizado') {
            throw new \DomainException('Solo puede enviar el reporte final de una orden finalizada.');
        }
        if (! $orden->reporteGuardadoExists($parcial)) {
            throw new \DomainException('Genere el PDF de esta orden antes de enviarlo.');
        }

        $tipo = $parcial ? 'resultados parciales' : 'resultados';

        return $this->enviar(
            $orden->cliente->telefono,
            'Le saluda '.config('laboratorio.nombre').". Adjuntamos sus {$tipo} de laboratorio. Para consultas: ".config('laboratorio.telefono'),
            Storage::disk('public')->get($orden->reporteGuardadoPath($parcial)),
            "orden-{$orden->id}".($parcial ? '-parcial' : '').'.pdf',
            $parcial ? 'reporte_parcial' : 'reporte_final',
            $orden->id,
        );
    }

    public function cotizacion(?string $telefono, string $pdf): EnvioWhatsApp
    {
        abort_unless(auth()->user()?->can('access_cotizaciones') && auth()->user()?->can('enviar_cotizacion_whatsapp'), 403);

        return $this->enviar($telefono, 'Le saluda '.config('laboratorio.nombre').'. Adjuntamos la cotización solicitada. Para consultas: '.config('laboratorio.telefono'), $pdf, 'cotizacion.pdf', 'cotizacion');
    }

    private function enviar(?string $telefono, string $mensaje, string $pdf, string $archivo, string $tipo, ?int $ordenId = null): EnvioWhatsApp
    {
        RegistroWhatsApp::evento('envio_solicitado', ['tipo' => $tipo]);
        $this->comprobarConfiguracion();
        $numero = self::numero($telefono);
        if (! $numero) {
            throw new \DomainException('Revise el teléfono del cliente e incluya el código de país (+503 o +1).');
        }
        if (! str_starts_with($pdf, '%PDF-') || strlen($pdf) > config('whatsapp.max_pdf_bytes')) {
            throw new \DomainException('El archivo debe ser un PDF de hasta 8 MB. Genere el reporte nuevamente.');
        }

        $huella = hash('sha256', $numero.$mensaje.$pdf);
        $lock = Cache::lock('whatsapp:'.$huella, 75);
        if (! $lock->get()) {
            throw new \DomainException('Este envío ya se está procesando. Espere unos segundos.');
        }

        try {
            // La incertidumbre se conserva un día: nunca reenviar automáticamente tras un timeout.
            $existente = EnvioWhatsApp::where('huella', $huella)->where(function ($q) {
                $q->whereIn('estado', ['enviando', 'desconocido'])->where('created_at', '>=', now()->subDay())
                    ->orWhere(fn ($q) => $q->where('estado', 'enviado')->where('created_at', '>=', now()->subMinutes(5)));
            })->latest()->first();
            if ($existente) {
                return $existente;
            }

            $envio = EnvioWhatsApp::create(['id' => (string) Str::uuid(), 'user_id' => auth()->id(), 'orden_id' => $ordenId,
                'telefono' => $numero, 'tipo' => $tipo, 'huella' => $huella, 'estado' => 'enviando']);
            try {
                // Sin retry: una respuesta perdida no significa que WhatsApp no lo haya enviado.
                $respuesta = Http::connectTimeout(3)->timeout(config('whatsapp.timeout'))
                    ->withHeaders(['X-Oncosavi-Token' => config('whatsapp.webhook_secret')])
                    ->post(config('whatsapp.webhook_url'), ['id' => $envio->id, 'phone' => $numero, 'message' => $mensaje,
                        'filename' => $archivo, 'pdf' => base64_encode($pdf)]);
                RegistroWhatsApp::evento('respuesta_n8n', ['http' => $respuesta->status()]);
                if (in_array($respuesta->status(), [401, 403, 404], true)) {
                    $envio->update(['estado' => 'fallido', 'codigo' => 'configuration']);
                } else {
                    $this->aplicarRespuesta($envio, $respuesta->json() ?? []);
                }
            } catch (\Throwable $e) {
                RegistroWhatsApp::evento('envio_sin_confirmacion', ['clase' => $e::class]);
                $envio->update(['estado' => 'desconocido', 'codigo' => 'connection']);
            }
            activity('WhatsApp')->causedBy(auth()->user())->performedOn($envio)
                ->withProperties(['tipo' => $tipo, 'orden_id' => $ordenId, 'estado' => $envio->estado])
                ->log('Solicitud de envío por WhatsApp');

            return $envio;
        } finally {
            $lock->release();
        }
    }

    public function actualizar(EnvioWhatsApp $envio): EnvioWhatsApp
    {
        abort_unless(auth()->user()?->can('manage_settings') || (auth()->user()?->canAny(['enviar_cotizacion_whatsapp', 'enviar_reporte_orden']) && $envio->user_id === auth()->id()), 403);
        if (in_array($envio->estado, ['enviando', 'desconocido'], true)) {
            try {
                $respuesta = $this->bridge()->get('/messages/'.$envio->id);
                if ($respuesta->successful()) {
                    $this->aplicarRespuesta($envio, $respuesta->json() ?? []);
                } elseif ($envio->created_at->lt(now()->subSeconds(90))) {
                    $envio->update(['estado' => 'desconocido']);
                }
            } catch (\Throwable $e) {
                if ($envio->created_at->lt(now()->subSeconds(90))) {
                    $envio->update(['estado' => 'desconocido']);
                }
            }
        }

        return $envio;
    }

    private function aplicarRespuesta(EnvioWhatsApp $envio, array $datos): void
    {
        $estado = match ($datos['status'] ?? '') {
            'sent' => ! empty($datos['messageId']) && is_string($datos['messageId']) ? 'enviado' : 'desconocido',
            'failed' => 'fallido',
            'sending' => 'enviando',
            default => 'desconocido',
        };
        $codigo = in_array($datos['code'] ?? '', ['not_connected', 'not_registered', 'invalid_request', 'busy'], true) ? $datos['code'] : null;
        $envio->update(['estado' => $estado, 'codigo' => $codigo,
            'message_id' => $estado === 'enviado' ? substr($datos['messageId'], 0, 150) : null,
            'enviado_at' => $estado === 'enviado' ? now() : null]);
    }

    public function conexion(string $accion = 'status'): array
    {
        abort_unless(auth()->user()?->can('manage_settings'), 403);
        if ($accion === 'connect') {
            RegistroWhatsApp::evento('vinculacion_solicitada');
        }
        $this->comprobarConfiguracion();
        try {
            $respuesta = $accion === 'connect' ? $this->bridge()->post('/connect') : $this->bridge()->get('/status');
            if ($accion === 'connect') {
                RegistroWhatsApp::evento('respuesta_puente', ['http' => $respuesta->status()]);
            }
            if (in_array($respuesta->status(), [401, 403], true)) {
                throw new \DomainException('La configuración de WhatsApp no coincide entre los servicios. Pida al administrador que actualice la instalación.');
            }
            if (! $respuesta->successful()) {
                throw new \RuntimeException;
            }
            $datos = $respuesta->json();

            return ['code' => in_array($datos['code'] ?? '', ['auth_expired', 'connection_replaced', 'protocol_error', 'qr_expired', 'network_error', 'session_error', 'connection_timeout', 'qr_error'], true) ? $datos['code'] : null,
                'status' => in_array($datos['status'] ?? '', ['connected', 'qr', 'connecting', 'disconnected'], true) ? $datos['status'] : 'disconnected',
                'qr' => isset($datos['qr']) && preg_match('#^data:image/png;base64,[A-Za-z0-9+/=]+$#', $datos['qr']) ? $datos['qr'] : null];
        } catch (\DomainException $e) {
            throw $e;
        } catch (\Throwable $e) {
            if ($accion === 'connect') {
                RegistroWhatsApp::evento('puente_no_disponible', ['clase' => $e::class]);
            }
            throw new \DomainException('No pudimos conectar con el servicio de WhatsApp. Revise que los contenedores estén iniciados.');
        }
    }

    private function bridge(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::baseUrl(rtrim(config('whatsapp.bridge_url'), '/'))->withToken(config('whatsapp.bridge_token'))->connectTimeout(2)->timeout(10);
    }

    private function comprobarConfiguracion(): void
    {
        if (! config('whatsapp.enabled') || ! config('whatsapp.webhook_secret') || ! config('whatsapp.bridge_token')) {
            RegistroWhatsApp::evento('configuracion_incompleta');
            throw new \DomainException('El envío automático aún no está configurado. Pida al administrador que active n8n y vincule el WhatsApp del laboratorio.');
        }
    }

    public static function numero(?string $telefono): ?string
    {
        $numero = preg_replace('/\D/', '', $telefono ?? '');
        if (strlen($numero) === 8) {
            $numero = '503'.$numero;
        } elseif (strlen($numero) === 10) {
            $numero = '1'.$numero;
        }

        return preg_match('/^(503[267]\d{7}|1[2-9]\d{9})$/', $numero) ? $numero : null;
    }
}
