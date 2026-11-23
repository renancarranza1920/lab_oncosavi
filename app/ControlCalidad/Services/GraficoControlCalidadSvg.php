<?php

namespace App\ControlCalidad\Services;

class GraficoControlCalidadSvg
{
    /** A chart per unit keeps historical measurements with different units apart. */
    public function imagenes(array $chart): array
    {
        $images = [];
        foreach (collect($chart['datasets'])->groupBy('unidad') as $unidad => $datasets) {
            $images[] = ['unidad' => $unidad, 'src' => 'data:image/svg+xml;base64,'.base64_encode($this->svg($chart['labels'], $datasets->values()->all(), $unidad))];
        }

        return $images;
    }

    private function svg(array $labels, array $datasets, string $unidad): string
    {
        $escape = fn ($s) => htmlspecialchars((string) $s, ENT_QUOTES | ENT_XML1, 'UTF-8');
        $coordinate = fn ($value) => number_format($value, 2, '.', '');
        $values = collect($datasets)->pluck('data')->flatten()->filter(fn ($v) => $v !== null);
        $pad = max(((float) $values->max() - (float) $values->min()) * 0.15, abs((float) $values->max()) * 0.02, 0.025);
        $min = floor(((float) $values->min() - $pad) * 100) / 100;
        $max = ceil(((float) $values->max() + $pad) * 100) / 100;
        $left = 85;
        $top = 26;
        $width = 820;
        $height = 245;
        $count = count($labels);
        $x = fn ($i) => $left + ($count > 1 ? $i * $width / ($count - 1) : $width / 2);
        $y = fn ($v) => $top + $height - (($v - $min) / ($max - $min)) * $height;
        $bands = '';
        $lines = '';
        $markers = '';
        $colors = ['#2563eb', '#7c3aed', '#0f766e', '#d97706'];
        $dashes = [[], [], [4, 4], [9, 4]];

        foreach (array_chunk($datasets, 4) as $group) {
            if (count($group) !== 4) {
                continue;
            }
            [$measured, , $low, $high] = $group;
            foreach ($this->segmentos($low['data']) as $indices) {
                $single = count($indices) === 1;
                $i = $indices[0];
                $start = $count === 1 ? $left : max($left, $x($i) - $width / max(2, 2 * ($count - 1)));
                $end = $count === 1 ? $left + $width : min($left + $width, $x($i) + $width / max(2, 2 * ($count - 1)));
                if ($single) {
                    $points = [$start.','.$y($low['data'][$i]), $end.','.$y($low['data'][$i]),
                        $end.','.$y($high['data'][$i]), $start.','.$y($high['data'][$i])];
                } else {
                    $points = [];
                    foreach ($indices as $index) {
                        $points[] = $coordinate($x($index)).','.$coordinate($y($low['data'][$index]));
                    }
                    foreach (array_reverse($indices) as $index) {
                        $points[] = $coordinate($x($index)).','.$coordinate($y($high['data'][$index]));
                    }
                }
                $bands .= '<polygon points="'.implode(' ', $points).'" fill="#e1f2ef"/>';
                // Extiende las referencias sin inventar una segunda medición.
                foreach ([2, 3, 1, 0] as $seriesIndex) {
                    if ($single && $seriesIndex === 0) {
                        continue;
                    }
                    $series = $group[$seriesIndex];
                    $color = $series['borderColor'] ?? $colors[$seriesIndex];
                    $dash = $series['borderDash'] ?? $dashes[$seriesIndex];
                    $pts = $single ? [$coordinate($start).','.$coordinate($y($series['data'][$i])),
                        $coordinate($end).','.$coordinate($y($series['data'][$i]))]
                        : array_map(fn ($index) => $coordinate($x($index)).','.$coordinate($y($series['data'][$index])), $indices);
                    $lines .= '<polyline data-series="'.$seriesIndex.'" points="'.implode(' ', $pts).'" fill="none" stroke="'.$escape($color).'" stroke-width="'.($seriesIndex === 0 ? '2.6' : '2.1').'" stroke-linecap="round"'
                        .($dash ? ' stroke-dasharray="'.implode(',', $dash).'"' : '').'/>';
                }
                foreach ($indices as $index) {
                    $px = $x($index);
                    $py = $y($measured['data'][$index]);
                    if (($measured['pointStyle'][$index] ?? null) === 'crossRot') {
                        $markers .= '<path d="M '.($px - 5).' '.($py - 5).' L '.($px + 5).' '.($py + 5).' M '.($px - 5).' '.($py + 5).' L '.($px + 5).' '.($py - 5).'" stroke="#dc2626" stroke-width="2.8"/>';
                    } else {
                        $markers .= '<circle cx="'.$coordinate($px).'" cy="'.$coordinate($py).'" r="4" fill="#2563eb"/>';
                    }
                }
            }
        }

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="960" height="415" viewBox="0 0 960 415"><rect width="960" height="415" fill="white"/>'.$bands;
        // La cuadrícula va sobre la franja para conservar sus líneas en el PDF.
        for ($tick = 0; $tick <= 5; $tick++) {
            $v = $min + ($max - $min) * $tick / 5;
            $py = $coordinate($y($v));
            $svg .= '<line data-grid="true" x1="'.$left.'" x2="'.($left + $width).'" y1="'.$py.'" y2="'.$py.'" stroke="#c0ccd7" stroke-width="1.1"/>'
                .'<text x="74" y="'.$coordinate($y($v) + 4).'" text-anchor="end" font-family="DejaVu Sans" font-size="12" fill="#475569">'.$escape(NumeroControlCalidad::mostrar($v)).'</text>';
        }
        $svg .= '<path d="M '.$left.' '.$top.' V '.($top + $height).' H '.($left + $width).'" fill="none" stroke="#94a3b8" stroke-width="1.2"/>'.$lines.$markers;
        $steps = array_unique(array_map(fn ($n) => (int) round(($count - 1) * $n / min(5, max(1, $count - 1))), range(0, min(5, max(0, $count - 1)))));
        foreach ($steps as $i) {
            if (isset($labels[$i])) {
                $svg .= '<text x="'.$coordinate($x($i)).'" y="296" text-anchor="middle" font-family="DejaVu Sans" font-size="10" fill="#475569">'.$escape($labels[$i]).'</text>';
            }
        }
        foreach (['Medido', 'Esperado', 'Mínimo', 'Máximo'] as $index => $label) {
            $lx = $left + ($index % 2) * 310;
            $ly = 330 + (intdiv($index, 2) * 26);
            $svg .= '<line x1="'.$lx.'" x2="'.($lx + 26).'" y1="'.$ly.'" y2="'.$ly.'" stroke="'.$colors[$index].'" stroke-width="2.6"'
                .($dashes[$index] ? ' stroke-dasharray="'.implode(',', $dashes[$index]).'"' : '').'/>'
                .'<text x="'.($lx + 37).'" y="'.($ly + 4).'" font-family="DejaVu Sans" font-size="12" fill="#334155">'.$label.'</text>';
        }
        $svg .= '<text x="'.$left.'" y="393" font-family="DejaVu Sans" font-size="11" fill="#b91c1c">X roja: medición fuera de rango</text></svg>';

        return $svg;
    }

    private function segmentos(array $data): array
    {
        $segments = [];
        $current = [];
        foreach ($data as $i => $value) {
            if ($value === null) {
                if ($current) {
                    $segments[] = $current;
                }
                $current = [];
            } else {
                $current[] = $i;
            }
        }
        if ($current) {
            $segments[] = $current;
        }

        return $segments;
    }
}
