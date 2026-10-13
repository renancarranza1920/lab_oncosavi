<?php

return [
    // Paleta de gráficos independiente de botones y etiquetas de estado.
    'charts' => [
        'line' => '#1E73BE',
        'line_fill' => 'rgba(30, 115, 190, 0.12)',
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

    // Azul Merino con tonos claros para texto y estados en modo oscuro.
    'primary' => [
        50 => '241, 248, 253',
        100 => '225, 239, 249',
        200 => '189, 223, 244',
        300 => '128, 188, 231',
        400 => '58, 143, 209',
        500 => '33, 119, 185',
        600 => '30, 115, 190',
        700 => '21, 90, 146',
        800 => '21, 74, 117',
        900 => '20, 63, 96',
        950 => '14, 41, 64',
    ],
];
