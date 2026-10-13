<?php

declare(strict_types=1);

// Compatibilidad con instalaciones anteriores: el reconocimiento se hace localmente.
// Esta ruta no carga configuración, no consulta la base y no llama a proveedores.
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
http_response_code(410);
echo json_encode(['success' => false, 'status' => 'gone',
    'mensaje' => 'El asistente reconoce los nombres de exámenes directamente en el navegador.'], JSON_UNESCAPED_UNICODE);
