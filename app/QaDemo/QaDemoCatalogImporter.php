<?php

namespace App\QaDemo;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class QaDemoCatalogImporter
{
    /** Only catalog identifiers, names, areas and units leave the clinical connection. */
    public function read(): array
    {
        $db = DB::connection();
        $rows = $db->table('pruebas as p')
            ->join('examens as e', 'e.id', '=', 'p.examen_id')
            ->leftJoin('tipo_examens as t', 't.id', '=', 'e.tipo_examen_id')
            ->where('p.estado', 'activo')
            ->where(function ($query): void { $query->where('e.es_externo', false)->orWhereNull('e.es_externo'); })
            ->orderBy('p.nombre')->limit(3000)
            ->get(['p.id as source_id', 'p.nombre as name', 'e.nombre as exam_name', 't.nombre as area'])
            ->filter(fn ($row) => str_contains(Str::lower(Str::ascii((string) $row->area)), 'quim'));
        $units = $db->table('valor_referencias')->whereIn('prueba_id', $rows->pluck('source_id'))
            ->whereNotNull('unidades')->where('unidades', '!=', '')->orderBy('id')
            ->get(['prueba_id', 'unidades'])->groupBy('prueba_id');

        return $rows->map(fn ($row) => [
            'source_id' => (int) $row->source_id,
            'name' => (string) $row->name,
            'exam_name' => (string) $row->exam_name,
            'area' => (string) ($row->area ?: 'Química sanguínea'),
            'unit' => (string) ($units->get($row->source_id)?->first()?->unidades ?? ''),
        ])->values()->all();
    }

    public function example(): array
    {
        return [
            ['source_id' => 199, 'name' => 'GLUCOSA EN AYUNA', 'exam_name' => 'GLUCOSA', 'unit' => 'mg/dL', 'area' => 'QUIMICA SANGUINEA'],
            ['source_id' => 193, 'name' => 'CREATININA SERICA', 'exam_name' => 'CREATININA', 'unit' => 'mg/dL', 'area' => 'QUIMICA SANGUINEA'],
            ['source_id' => 190, 'name' => 'COLESTEROL TOTAL', 'exam_name' => 'COLESTEROL TOTAL', 'unit' => 'mg/dL', 'area' => 'QUIMICA SANGUINEA'],
            ['source_id' => 229, 'name' => 'TRIGLICERIDOS', 'exam_name' => 'TRIGLICÉRIDOS', 'unit' => 'mg/dL', 'area' => 'QUIMICA SANGUINEA'],
            ['source_id' => 198, 'name' => 'GAMMA GLUTAMIL TRANSPEPTIDASA (GGT)', 'exam_name' => 'GAMMA GLUTAMIL (GCT)', 'unit' => 'U/L', 'area' => 'QUIMICA SANGUINEA'],
        ];
    }
}
