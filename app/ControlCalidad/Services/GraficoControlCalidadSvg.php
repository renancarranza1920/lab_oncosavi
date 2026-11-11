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
        $values = collect($datasets)->pluck('data')->flatten()->filter(fn ($v) => $v !== null);
        $min = (float) $values->min();
        $max = (float) $values->max();
        $pad = max(($max - $min) * 0.15, abs($max) * 0.02, 0.01);
        $min -= $pad;
        $max += $pad;
        $left = 75;
        $top = 25;
        $width = 850;
        $height = 255;
        $count = count($labels);
        $x = fn ($i) => $left + ($count > 1 ? $i * $width / ($count - 1) : $width / 2);
        $y = fn ($v) => $top + $height - (($v - $min) / ($max - $min)) * $height;
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="960" height="375" viewBox="0 0 960 375"><rect width="960" height="375" fill="white"/>';
        for ($tick = 0; $tick <= 5; $tick++) {
            $v = $min + ($max - $min) * $tick / 5;
            $py = $y($v);
            $svg .= '<line x1="'.$left.'" x2="'.($left + $width).'" y1="'.$py.'" y2="'.$py.'" stroke="#e2e8f0"/><text x="65" y="'.($py + 4).'" text-anchor="end" font-family="DejaVu Sans" font-size="11" fill="#526377">'.$escape(round($v, 4)).'</text>';
        }
        foreach (array_chunk($datasets, 4) as $group) {
            if (count($group) !== 4) {
                continue;
            }
            [$measured, $expected, $low, $high] = $group;
            $segments = [];
            $segment = [];
            foreach ($low['data'] as $i => $v) {
                if ($v === null) {
                    if ($segment) {
                        $segments[] = $segment;
                    }
                    $segment = [];
                } else {
                    $segment[] = $i;
                }
            }
            if ($segment) {
                $segments[] = $segment;
            }
            foreach ($segments as $indices) {
                $points = [];
                if (count($indices) === 1) {
                    $i = $indices[0];
                    $start = $count === 1 ? $left : max($left, $x($i) - $width / max(2, 2 * ($count - 1)));
                    $end = $count === 1 ? $left + $width : min($left + $width, $x($i) + $width / max(2, 2 * ($count - 1)));
                    $points = [$start.','.$y($low['data'][$i]), $end.','.$y($low['data'][$i]), $end.','.$y($high['data'][$i]), $start.','.$y($high['data'][$i])];
                } else {
                    foreach ($indices as $i) {
                        $points[] = $x($i).','.$y($low['data'][$i]);
                    }
                    foreach (array_reverse($indices) as $i) {
                        $points[] = $x($i).','.$y($high['data'][$i]);
                    }
                }
                $svg .= '<polygon points="'.implode(' ', $points).'" fill="#d6f2ed"/>';
                foreach ([[$low, '#64748b', true], [$high, '#64748b', true], [$expected, '#0f766e', true], [$measured, '#2563eb', false]] as [$series, $color, $dash]) {
                    $pts = array_map(fn ($i) => $x($i).','.$y($series['data'][$i]), $indices);
                    $svg .= '<polyline points="'.implode(' ', $pts).'" fill="none" stroke="'.$color.'" stroke-width="'.($dash ? 1 : 2).'"'.($dash ? ' stroke-dasharray="5,4"' : '').'/>';
                }
                foreach ($indices as $i) {
                    $px = $x($i);
                    $py = $y($measured['data'][$i]);
                    if ($measured['pointStyle'][$i] === 'crossRot') {
                        $svg .= '<path d="M '.($px - 5).' '.($py - 5).' L '.($px + 5).' '.($py + 5).' M '.($px - 5).' '.($py + 5).' L '.($px + 5).' '.($py - 5).'" stroke="#dc2626" stroke-width="2.5"/>';
                    } else {
                        $svg .= '<circle cx="'.$px.'" cy="'.$py.'" r="3.5" fill="#2563eb"/>';
                    }
                }
            }
        }
        $steps = array_unique(array_map(fn ($n) => (int) round(($count - 1) * $n / min(5, max(1, $count - 1))), range(0, min(5, max(0, $count - 1)))));
        foreach ($steps as $i) {
            if (! isset($labels[$i])) {
                continue;
            }
            $svg .= '<text x="'.$x($i).'" y="305" text-anchor="middle" font-family="DejaVu Sans" font-size="10" fill="#526377">'.$escape($labels[$i]).'</text>';
        }
        $svg .= '<text x="75" y="337" font-family="DejaVu Sans" font-size="12" fill="#172435">'.$escape('Unidad: '.$unidad.' · Franja: rango de control · Línea azul: medición · Línea verde: esperado').'</text>';
        $svg .= '<text x="75" y="359" font-family="DejaVu Sans" font-size="12" fill="#b91c1c">X roja: medición fuera de rango</text></svg>';

        return $svg;
    }
}
