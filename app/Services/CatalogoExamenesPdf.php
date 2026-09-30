<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Dompdf\Dompdf;
use Illuminate\Validation\ValidationException;

class CatalogoExamenesPdf
{
    public const COLUMN_HEIGHT = 690;
    public const COLUMN_WIDTH = 178.33;

    public function organizar(iterable $areas, iterable $perfiles): array
    {
        $metrics = (new Dompdf())->getFontMetrics();
        $font = $metrics->getFont('DejaVu Sans', 'normal');
        $bold = $metrics->getFont('DejaVu Sans', 'bold');
        $sections = [];
        foreach ($areas as $area) {
            $rows = [];
            foreach ($area->examenes as $examen) {
                $rows[] = ['nombre' => $examen->nombre, 'precio' => (float) $examen->precio];
            }
            if ($rows) $sections[] = ['titulo' => $area->nombre, 'perfil' => false, 'precio' => null, 'filas' => $rows];
        }
        foreach ($perfiles as $perfil) {
            $rows = [];
            foreach ($perfil->examenes as $examen) {
                $rows[] = ['nombre' => $examen->nombre, 'precio' => null];
            }
            $sections[] = ['titulo' => $perfil->nombre, 'perfil' => true, 'precio' => (float) $perfil->precio, 'filas' => $rows];
        }

        foreach ([8.5, 8.2, 8.0, 7.8] as $size) {
            $prepared = [];
            foreach ($sections as $section) {
                $section['lineas'] = $this->lineas(mb_strtoupper($section['titulo']), $section['perfil'] ? 121 : 175, $size, $bold, $metrics);
                $section['alto_titulo'] = count($section['lineas']) * ($size + 1.5) + 10;
                foreach ($section['filas'] as &$row) {
                    $row['lineas'] = $this->lineas($row['nombre'], $row['precio'] === null ? 158 : 121, $size, $font, $metrics);
                    $row['alto'] = max(13, count($row['lineas']) * ($size + 1.5) + 2);
                }
                unset($row);
                $prepared[] = $section;
            }
            $columns = $this->distribuir($prepared, self::COLUMN_HEIGHT);
            if (count($columns) > 6) continue;
            $overflow = false;
            foreach ($columns as $column) {
                foreach ($column as $fragment) {
                    $bottom = $fragment['y'] + $fragment['alto_titulo'] + array_sum(array_column($fragment['filas'], 'alto'));
                    if ($bottom > self::COLUMN_HEIGHT) $overflow = true;
                }
            }
            if ($overflow) continue;

            // Balancear las columnas sin partir filas ni separar un título de su primer examen.
            $target = count($columns) <= 3 ? 3 : 6;
            $low = 40;
            $high = self::COLUMN_HEIGHT;
            while ($high - $low > 1) {
                $mid = (int) (($low + $high) / 2);
                if (count($this->distribuir($prepared, $mid)) <= $target) $high = $mid;
                else $low = $mid;
            }
            $columns = $this->distribuir($prepared, $high);
            return ['paginas' => array_chunk($columns, 3), 'tamano' => $size, 'interlineado' => $size + 1.5];
        }

        throw ValidationException::withMessages([
            'catalogo' => 'El catálogo excede dos páginas A4 legibles. Revise los exámenes y perfiles activos antes de imprimir.',
        ]);
    }

    private function lineas(string $text, float $width, float $size, string $font, $metrics): array
    {
        $lines = [];
        $line = '';
        foreach (preg_split('/\s+/u', trim($text)) as $word) {
            $candidate = $line === '' ? $word : $line . ' ' . $word;
            if ($line !== '' && $metrics->getTextWidth($candidate, $font, $size) > $width) {
                $lines[] = $line;
                $line = '';
            }
            // También dividir identificadores largos sin espacios, sin recortar el nombre.
            foreach (mb_str_split($word) as $index => $letter) {
                $candidate = $line . ($index === 0 && $line !== '' ? ' ' : '') . $letter;
                if ($metrics->getTextWidth($candidate, $font, $size) > $width && $line !== '') {
                    $lines[] = $line;
                    $line = $letter;
                } else $line = $candidate;
            }
        }
        if ($line !== '' || !$lines) $lines[] = $line;
        return $lines;
    }

    private function distribuir(array $sections, float $limit): array
    {
        $columns = [[]];
        $column = 0;
        $y = 0;
        foreach ($sections as $section) {
            $remaining = $section['filas'];
            $continued = false;
            $sectionHeight = $section['alto_titulo'] + array_sum(array_column($remaining, 'alto'));
            if ($y > 0 && $sectionHeight <= $limit && $section['perfil'] && $y + $sectionHeight > $limit) {
                $columns[++$column] = [];
                $y = 0;
            }
            do {
                $firstHeight = $remaining[0]['alto'] ?? 0;
                if ($y > 0 && $y + $section['alto_titulo'] + $firstHeight > $limit) {
                    $columns[++$column] = [];
                    $y = 0;
                }
                // Un fragmento siempre incluye al menos una fila, aun en límites de búsqueda muy bajos.
                $fragment = $section;
                $fragment['y'] = $y;
                $fragment['continuacion'] = $continued;
                $fragment['filas'] = [];
                $y += $section['alto_titulo'];
                while ($remaining) {
                    if ($fragment['filas'] && $y + $remaining[0]['alto'] > $limit) break;
                    $row = array_shift($remaining);
                    $row['y'] = $y - $fragment['y'];
                    $fragment['filas'][] = $row;
                    $y += $row['alto'];
                }
                $columns[$column][] = $fragment;
                $y += 4;
                if ($remaining) {
                    $columns[++$column] = [];
                    $y = 0;
                    $continued = true;
                }
            } while ($remaining);
        }
        return $columns;
    }

    public function generar(iterable $areas, iterable $perfiles): \Barryvdh\DomPDF\PDF
    {
        $layout = $this->organizar($areas, $perfiles);
        $pdf = Pdf::setOptions(['isRemoteEnabled' => false, 'dpi' => 96, 'chroot' => base_path()])
            ->loadView('pdf.reporte-examenes', $layout + [
                'logoPath' => public_path(config('laboratorio.logo')),
            ])->setPaper('a4', 'portrait');
        $pdf->render();
        if ($pdf->getDomPDF()->getCanvas()->get_page_count() > 2) {
            throw ValidationException::withMessages(['catalogo' => 'No se pudo ajustar el catálogo a dos páginas A4 sin cortar contenido.']);
        }
        return $pdf;
    }
}
