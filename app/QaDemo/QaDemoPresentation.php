<?php

namespace App\QaDemo;

final class QaDemoPresentation
{
    /** Existing demo records keep their original snapshots; only their labels change. */
    private const LEGACY_TEXT = [
        'Mindray BS-240 (ilustrativo)' => 'Mindray BS-240',
        'Equipo de demostración. Verificar modelo, método, materiales y límites antes de un uso operativo.' => 'Analizador de química clínica.',
        'Método ilustrativo: confirmar con el laboratorio' => 'Método del equipo',
        'unidad ilustrativa' => 'Unidad del control',
        'Suero de control multianalito (demostración)' => 'Suero de control multianalito',
        'Rafael Hernández (demostración)' => 'Rafael Hernández',
        'Daniela Martínez (demostración)' => 'Daniela Martínez',
        'Registro ilustrativo para revisar el módulo.' => 'Control habitual de química.',
        'Repetición revisada. Ejemplo de seguimiento; no certifica una calibración.' => 'Repetición revisada.',
        'Registro de demostración revisado.' => 'Control revisado.',
    ];

    public static function text(string $value): string
    {
        return self::LEGACY_TEXT[$value]
            ?? preg_replace('/^(QCQ-\d{4}) · Datos de demostración$/u', '$1', $value);
    }

    public static function data(array $data): array
    {
        foreach ($data as $key => $value) {
            $data[$key] = is_array($value) ? self::data($value) : (is_string($value) ? self::text($value) : $value);
        }

        return $data;
    }
}
