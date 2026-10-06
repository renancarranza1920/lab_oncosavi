<?php

namespace App\Support;

use App\Models\Cliente;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;
use Illuminate\Validation\ValidationException;

class DestinoWhatsApp
{
    public static function formulario(Cliente $cliente): array
    {
        $opciones = [];
        foreach ($cliente->telefonos_contacto as $numero) {
            $opciones[$numero] = '+'.$numero;
        }
        $opciones['otro'] = 'Escribir otro número';
        if (filled($cliente->correo)) {
            $opciones['correo'] = 'Solo por correo electrónico';
        }

        return [
            Select::make('destino_whatsapp')->label('¿A qué número quieres enviar?')
                ->options($opciones)->placeholder('Selecciona un número')->required()->searchable()->live()
                ->helperText('Elige el destinatario. No se selecciona ningún teléfono automáticamente.'),
            TelefonoCliente::campoCompacto('telefono_destino', true)
                ->visible(fn (Get $get) => $get('destino_whatsapp') === 'otro'),
        ];
    }

    public static function resolver(Cliente $cliente, array $data): ?string
    {
        $destino = (string) ($data['destino_whatsapp'] ?? '');
        if ($destino === 'correo' && filled($cliente->correo)) {
            return null;
        }
        if ($destino === 'otro') {
            $numero = (string) ($data['telefono_destino'] ?? '');
            validator(['telefono_destino' => $numero], ['telefono_destino' => ['required', 'regex:/^[1-9][0-9]{6,14}$/']],
                ['telefono_destino.regex' => 'Escribe un teléfono válido con su código de país.'])->validate();

            return $numero;
        }
        if ($destino !== '' && in_array($destino, $cliente->telefonos_contacto, true)) {
            return $destino;
        }

        throw ValidationException::withMessages(['destino_whatsapp' => 'Selecciona a qué número quieres enviar el PDF.']);
    }
}
