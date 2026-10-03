<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class DiagnosticarWhatsApp extends Command
{
    protected $signature = 'whatsapp:diagnostico';

    protected $description = 'Comprueba los servicios de WhatsApp sin imprimir secretos ni iniciar una vinculación';

    public function handle(): int
    {
        $configurado = config('whatsapp.enabled') && config('whatsapp.webhook_secret') && config('whatsapp.bridge_token');
        $this->line('Configuración: '.($configurado ? 'completa' : 'incompleta; ejecute bash docker/whatsapp/iniciar.sh'));
        if (! $configurado) {
            return self::FAILURE;
        }
        try {
            $respuesta = Http::withToken(config('whatsapp.bridge_token'))->connectTimeout(2)->timeout(5)
                ->get(rtrim(config('whatsapp.bridge_url'), '/').'/status');
            $this->line('Puente WhatsApp: HTTP '.$respuesta->status());
            if (! $respuesta->successful()) {
                $this->warn(in_array($respuesta->status(), [401, 403]) ? 'Las claves de los servicios no coinciden.' : 'El puente no está disponible.');
                return self::FAILURE;
            }
            $estado = $respuesta->json('status');
            $this->line('Sesión: '.(in_array($estado, ['disconnected', 'connecting', 'qr', 'connected'], true) ? $estado : 'respuesta no válida'));
            $codigo = $respuesta->json('code');
            if (in_array($codigo, ['auth_expired', 'connection_replaced', 'protocol_error', 'qr_expired', 'network_error', 'session_error', 'connection_timeout', 'qr_error'], true)) {
                $this->line('Motivo: '.$codigo);
            }
        } catch (\Throwable) {
            $this->error('No se pudo contactar al puente desde app. Revise que ambos contenedores estén iniciados y compartan la red.');
            return self::FAILURE;
        }
        $this->line('Esta comprobación no envía mensajes ni imprime el QR.');

        return self::SUCCESS;
    }
}
