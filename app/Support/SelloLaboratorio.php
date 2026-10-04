<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SelloLaboratorio
{
    private const CONFIG = 'laboratorio/sello.json';

    public static function path(): ?string
    {
        if (!Storage::disk('local')->exists(self::CONFIG)) {
            return null;
        }
        $data = json_decode(Storage::disk('local')->get(self::CONFIG) ?? '{}', true);

        return $data['path'] ?? null;
    }

    public static function guardar(?string $path): void
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);
        if ($path && (!str_starts_with($path, 'sellos/laboratorio/') || str_contains($path, '..')
            || !Storage::disk('public')->exists($path)
            || (@getimagesize(Storage::disk('public')->path($path))[2] ?? null) !== IMAGETYPE_PNG)) {
            throw ValidationException::withMessages(['data.sello_laboratorio' => 'Seleccione un sello PNG del laboratorio.']);
        }
        if (!Storage::disk('local')->put(self::CONFIG, json_encode(['path' => $path]))) {
            throw ValidationException::withMessages(['data.sello_laboratorio' => 'No se pudo guardar el sello del laboratorio. Inténtelo nuevamente.']);
        }
    }

    public static function base64(): ?string
    {
        $path = self::path();
        if (!$path || !Storage::disk('public')->exists($path)) {
            return null;
        }

        return 'data:image/png;base64,' . base64_encode(Storage::disk('public')->get($path));
    }
}
