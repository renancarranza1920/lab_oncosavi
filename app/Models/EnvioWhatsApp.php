<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnvioWhatsApp extends Model
{
    protected $table = 'envios_whatsapp';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $hidden = ['telefono', 'huella'];

    protected function casts(): array
    {
        return ['telefono' => 'encrypted', 'enviado_at' => 'datetime'];
    }

    public function mensajeEstado(): string
    {
        return match ($this->estado) {
            'enviado' => 'WhatsApp confirmó el envío del PDF. Esto no confirma que el destinatario lo haya leído.',
            'enviando' => 'El envío está en proceso. Espere antes de volver a intentarlo.',
            'desconocido' => 'No pudimos confirmar el envío. Revise el chat del teléfono vinculado o actualice el estado en Envíos WhatsApp antes de reenviar.',
            default => match ($this->codigo) {
                'not_connected' => 'El WhatsApp del laboratorio está desconectado. Pida al administrador que lo vincule nuevamente.',
                'not_registered' => 'Ese número no tiene una cuenta de WhatsApp. Revise el teléfono del cliente.',
                'invalid_request' => 'Revise el número y el PDF antes de intentar enviarlos nuevamente.',
                'busy' => 'Se está procesando otro envío. Espere unos segundos e inténtelo nuevamente.',
                default => 'No se pudo iniciar el envío. Revise la conexión de WhatsApp y la configuración de n8n.',
            },
        };
    }
}
