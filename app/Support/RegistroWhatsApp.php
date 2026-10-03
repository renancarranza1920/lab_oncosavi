<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

class RegistroWhatsApp
{
    public static function evento(string $evento, array $contexto = []): void
    {
        // No registrar excepciones completas, tokens, teléfonos, QR ni documentos.
        $seguros = array_intersect_key($contexto, array_flip(['estado', 'http', 'tipo', 'clase']));
        Log::channel('whatsapp')->info('WhatsApp: '.$evento, $seguros);
    }
}
