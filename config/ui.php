<?php

return [
    // Paleta de gráficos independiente de botones y etiquetas de estado.
    'charts' => [
        'line' => '#3B82F6',
        'line_fill' => 'rgba(59, 130, 246, 0.12)',
        'bars' => ['#6366F1', '#3B82F6', '#0891B2', '#0D9488', '#8B5CF6'],
        'states' => [
            'pendiente' => '#8B5CF6',
            'en proceso' => '#3B82F6',
            'finalizado' => '#059669',
            'pausada' => '#D97706',
            'cancelado' => '#E11D48',
        ],
        'fallback' => '#94A3B8',
    ],

    // Escala institucional: marino para la marca y celeste legible en modo oscuro.
    'primary' => [
        50 => '240, 247, 250',
        100 => '224, 240, 245',
        200 => '191, 221, 231',
        300 => '165, 209, 223',
        400 => '100, 171, 198',
        500 => '50, 118, 140',
        600 => '38, 88, 110',
        700 => '29, 61, 86',
        800 => '23, 41, 67',
        900 => '15, 25, 52',
        950 => '9, 11, 59',
    ],
];
