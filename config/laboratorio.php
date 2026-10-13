<?php

$nombre = 'LABORATORIO CLÍNICO MERINO';
$correo = trim((string) env('LABORATORIO_CORREO', ''));
$whatsappUrl = 'https://wa.me/50375954210?'.http_build_query([
    'text' => 'Hola, deseo solicitar información sobre los servicios de '.$nombre.'.',
], '', '&', PHP_QUERY_RFC3986);

return [
    // Reactivar cuando estén disponibles las etiquetas y la impresora.
    'impresion_etiquetas_habilitada' => env('IMPRESION_ETIQUETAS_HABILITADA', false),
    'nombre' => $nombre,
    'nombre_corto' => 'MERINO',
    'sede' => 'San Vicente',
    'correo' => $correo,
    'telefono' => '+503 2606-6596',
    'telefono_uri' => 'tel:+50326066596',
    'whatsapp' => '+503 7595-4210',
    'whatsapp_url' => $whatsappUrl,
    'correo_url' => $correo !== ''
        ? 'https://mail.google.com/mail/?'.http_build_query([
            'view' => 'cm', 'fs' => '1', 'to' => $correo,
            'su' => 'Consulta desde el sitio web de '.$nombre,
        ], '', '&', PHP_QUERY_RFC3986)
        : $whatsappUrl,
    'direccion' => '4ª Calle Oriente #6, B° San Francisco, San Vicente, El Salvador.',
    'mapa_url' => 'https://www.bing.com/maps?'.http_build_query([
        'where1' => 'Laboratorio Clínico Merino, 4ª Calle Oriente #6, B° San Francisco, San Vicente, El Salvador',
    ], '', '&', PHP_QUERY_RFC3986),
    'web_url' => 'https://app.laboratorioclinicomerino.com',
    'logo' => 'images/merino-logo.png',
    'logo_claro' => 'images/merino-horizontal-claro.png',
    'logo_oscuro' => 'images/merino-horizontal-oscuro.png',
    'favicon' => 'images/merino-icono.png',
    'admin_nickname' => env('LAMERI_ADMIN_NICKNAME', 'lameri'),
    'admin_nombre' => env('LAMERI_ADMIN_NAME', 'Administración Lameri'),
    'admin_correo' => env('LAMERI_ADMIN_EMAIL', $correo),
    'admin_password' => env('LAMERI_ADMIN_PASSWORD'),
    'colores' => [
        'azul' => '#1E73BE',
        'azul_claro' => '#3A8FD1',
        'azul_oscuro' => '#155A92',
        'naranja' => '#F6A623',
        'verde' => '#8BC34A',
        // Compatibilidad con vistas que consumen las claves históricas del tema.
        'marino' => '#155A92',
        'celeste' => '#1E73BE',
        'rojo' => '#F6A623',
        'blanco' => '#FFFFFF',
    ],
];
