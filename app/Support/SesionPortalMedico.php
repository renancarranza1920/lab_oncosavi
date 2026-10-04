<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SesionPortalMedico
{
    private const ARCHIVO = 'portal-medicos/version-reinicio.txt';

    public static function version(): string
    {
        $disk = Storage::disk('local');

        return $disk->exists(self::ARCHIVO) ? trim($disk->get(self::ARCHIVO)) : '';
    }

    public static function invalidar(): void
    {
        if (!Storage::disk('local')->put(self::ARCHIVO, (string) Str::uuid())) {
            throw new \RuntimeException('No se pudo renovar la versión de las sesiones médicas.');
        }
    }
}
