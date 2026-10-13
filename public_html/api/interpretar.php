<?php

declare(strict_types=1);

ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('Cross-Origin-Resource-Policy: same-origin');
define('LAMERI_PUBLIC_API', true);
$response = ['success' => false, 'status' => 'unavailable', 'kind' => 'none', 'keys' => [], 'needs_clarification' => false];
http_response_code(503);

try {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405); header('Allow: POST');
        throw new RuntimeException('Método no válido.');
    }
    if (($_SERVER['HTTP_SEC_FETCH_SITE'] ?? 'same-origin') === 'cross-site') {
        http_response_code(403); throw new RuntimeException('Origen no válido.');
    }
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        $origin = parse_url($_SERVER['HTTP_ORIGIN']);
        $requestHost = parse_url('https://'.($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST);
        if (!is_array($origin) || !in_array($origin['scheme'] ?? '', ['http', 'https'], true)
            || strtolower($origin['host'] ?? '') !== strtolower((string) $requestHost)) {
            http_response_code(403); throw new RuntimeException('Origen no válido.');
        }
    }
    if (strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0])) !== 'application/json') {
        http_response_code(415); throw new RuntimeException('Formato no válido.');
    }
    $raw = file_get_contents('php://input', false, null, 0, 8193);
    if (!is_string($raw) || strlen($raw) > 8192) {
        http_response_code(413); throw new RuntimeException('Consulta demasiado grande.');
    }
    require __DIR__.'/lib/configuracion.php';
    require __DIR__.'/lib/interpretacion.php';
    try {
        $consulta = \LameriPublica\validarConsultaIa(json_decode($raw, true, 16, JSON_THROW_ON_ERROR));
    } catch (Throwable $invalid) { http_response_code(400); throw $invalid; }
    if (!\LameriPublica\consultaPublica($consulta['consulta'])) {
        http_response_code(200);
        $response = array_replace($response, ['success' => true, 'status' => 'ready', 'kind' => 'contact']);
    } else {
        $privatePath = dirname(__DIR__, 2).'/configuracion/lameri-publica.php';
        $config = \LameriPublica\cargarConfiguracion($privatePath);
        if (!$config['ia']['enabled'] || !extension_loaded('curl')) throw new RuntimeException('IA desactivada.');
        require __DIR__.'/lib/catalogo.php';
        require __DIR__.'/lib/cache.php';
        $pdo = \LameriPublica\conectarMysql($config['mysql']);
        $cache = new \LameriPublica\CacheCatalogo(dirname($privatePath).'/cache', $config['mysql']);
        $catalogo = $cache->leer();
        if ($catalogo === null) {
            $catalogo = (new \LameriPublica\RepositorioCatalogo($pdo))->consultar();
            $cache->guardar($catalogo);
        }
        $refs = \LameriPublica\referenciasCatalogo($catalogo, $consulta['scope']);
        if (!$refs) {
            $interpretacion = ['kind' => 'none', 'keys' => [], 'needs_clarification' => false];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'desconocido';
            if (!\LameriPublica\reservarConsultaIa(dirname($privatePath).'/cache', $config['ia'], $ip)) {
                http_response_code(429); header('Retry-After: 60'); throw new RuntimeException('Límite alcanzado.');
            }
            $interpretacion = \LameriPublica\consultarOpenAI($config['ia'], $consulta, $refs);
        }
        $response = array_replace($response, $interpretacion, ['success' => true, 'status' => 'ready']);
        http_response_code(200);
    }
} catch (Throwable $error) {
    // No se publican claves, SQL, respuestas del proveedor ni el texto de la pregunta.
}
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
