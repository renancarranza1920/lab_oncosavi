<?php

namespace App\Services;

use App\Models\Examen;
use App\Models\Perfil;

class OrdenSelectionService
{
    public static function perfilesUnicos(array $perfiles): array
    {
        return self::unicosPorCampo($perfiles, 'perfil_id');
    }

    public static function examenesCubiertosPorPerfiles(array $perfiles): array
    {
        $perfilIds = collect(self::perfilesUnicos($perfiles))
            ->pluck('perfil_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($perfilIds->isEmpty()) {
            return [];
        }

        return Perfil::query()
            ->whereIn('id', $perfilIds)
            ->with(['examenes' => fn ($query) => $query->where('estado', 1)])
            ->get()
            ->flatMap(fn (Perfil $perfil) => $perfil->examenes->pluck('id'))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public static function examenesCobrables(array $perfiles, array $examenes): array
    {
        $cubiertosPorPerfil = array_flip(self::examenesCubiertosPorPerfiles($perfiles));
        $procesados = [];
        $cobrables = [];

        foreach ($examenes as $item) {
            $examenId = (int) ($item['examen_id'] ?? 0);

            if ($examenId === 0 || isset($cubiertosPorPerfil[$examenId]) || isset($procesados[$examenId])) {
                continue;
            }

            $procesados[$examenId] = true;
            $cobrables[] = $item;
        }

        return $cobrables;
    }

    public static function subtotal(array $perfiles, array $examenes): float
    {
        $total = 0;

        foreach (self::perfilesUnicos($perfiles) as $item) {
            $total += self::precioItem($item, 'perfil_id', Perfil::class);
        }

        foreach (self::examenesCobrables($perfiles, $examenes) as $item) {
            $total += self::precioItem($item, 'examen_id', Examen::class);
        }

        return $total;
    }

    private static function unicosPorCampo(array $items, string $campo): array
    {
        $vistos = [];
        $unicos = [];

        foreach ($items as $item) {
            $id = (int) ($item[$campo] ?? 0);

            if ($id === 0 || isset($vistos[$id])) {
                continue;
            }

            $vistos[$id] = true;
            $unicos[] = $item;
        }

        return $unicos;
    }

    private static function precioItem(array $item, string $idCampo, string $modelClass): float
    {
        if (isset($item['precio_hidden'])) {
            return (float) $item['precio_hidden'];
        }

        if (isset($item['precio'])) {
            return (float) $item['precio'];
        }

        $id = (int) ($item[$idCampo] ?? 0);

        return $id > 0 ? (float) ($modelClass::find($id)?->precio ?? 0) : 0;
    }
}
