<?php

namespace App\Support;

use App\Models\EnvioWhatsApp;
use Filament\Notifications\Notification;

class AvisoWhatsApp
{
    public static function enviar(callable $accion): void
    {
        try {
            self::estado($accion());
        } catch (\DomainException $e) {
            Notification::make()->title('No se pudo enviar por WhatsApp')->body($e->getMessage())->warning()->send();
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            throw $e;
        } catch (\Throwable $e) {
            RegistroWhatsApp::evento('solicitud_interrumpida', ['clase' => $e::class]);
            // No mostrar mensajes internos, rutas, credenciales o respuestas del proveedor.
            Notification::make()->title('No se pudo completar la solicitud')
                ->body('Revise Envíos WhatsApp antes de intentarlo nuevamente. Si persiste, contacte al administrador.')->danger()->send();
        }
    }

    public static function estado(EnvioWhatsApp $envio): void
    {
        $aviso = Notification::make()->title(match ($envio->estado) {
            'enviado' => 'PDF enviado por WhatsApp', 'fallido' => 'No se pudo enviar el PDF',
            'enviando' => 'Envío en proceso', default => 'Envío sin confirmar',
        })->body($envio->mensajeEstado());
        match ($envio->estado) {
            'enviado' => $aviso->success(), 'fallido' => $aviso->danger(), default => $aviso->warning()->persistent(),
        };
        $aviso->send();
    }
}
