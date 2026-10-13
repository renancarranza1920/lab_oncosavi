<?php

declare(strict_types=1);

// Endpoint independiente: no carga Laravel, Composer, .env ni la aplicación clínica.
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('Cross-Origin-Resource-Policy: same-origin');

define('LAMERI_PUBLIC_API', true);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$response = [
    'success' => false,
    'status' => 'unavailable',
    'mensaje' => 'Los precios y el catálogo no están disponibles en este momento. Puedes consultar información general o contactar al laboratorio.',
    'moneda' => 'USD',
    'consultado_en' => null,
    'examens' => [],
    'perfiles' => [],
    'contacto' => new stdClass(),
];

if (!in_array($method, ['GET', 'HEAD'], true)) {
    http_response_code(405);
    header('Allow: GET, HEAD');
    $response['status'] = 'method_not_allowed';
    $response['mensaje'] = 'Utiliza la consulta del catálogo desde el sitio del laboratorio.';
} else {
    try {
        require __DIR__.'/lib/configuracion.php';
        require __DIR__.'/lib/catalogo.php';
        require __DIR__.'/lib/cache.php';

        // Ruta fija privada; ningún parámetro de la petición puede modificarla.
        $privateConfigPath = dirname(__DIR__, 2).'/configuracion/lameri-publica.php';
        $config = \LameriPublica\cargarConfiguracion($privateConfigPath);
        $response['contacto'] = (object) $config['contacto'];

        // Comprobar la conexión incluso con caché reciente: una BD inaccesible
        // nunca se sustituye por un catálogo antiguo presentado como disponible.
        $pdo = \LameriPublica\conectarMysql($config['mysql']);
        $cache = new \LameriPublica\CacheCatalogo(dirname($privateConfigPath).'/cache', $config['mysql']);
        $catalogo = $cache->leer();
        if ($catalogo === null) {
            $catalogo = (new \LameriPublica\RepositorioCatalogo($pdo))->consultar();
            $cache->guardar($catalogo);
        }

        $response = array_merge($response, $catalogo, [
            'success' => true,
            'status' => 'ready',
            'mensaje' => 'Catálogo disponible.',
            'asistente' => ['ia' => false],
        ]);
        http_response_code(200);
    } catch (Throwable $error) {
        // El navegador nunca recibe rutas privadas, SQL, credenciales ni excepciones.
        http_response_code(503);
        header('Retry-After: 60');
    }
}

if ($method !== 'HEAD') {
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
}
