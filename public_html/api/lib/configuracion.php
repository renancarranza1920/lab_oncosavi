<?php

declare(strict_types=1);

namespace LameriPublica;

use PDO;
use RuntimeException;

if (!defined('LAMERI_PUBLIC_API')) {
    http_response_code(404);
    exit;
}

function cargarConfiguracion(string $path): array
{
    if (!is_file($path) || !is_readable($path)) {
        throw new RuntimeException('Configuración no disponible.');
    }

    // No publicar ninguna salida accidental del archivo privado.
    ob_start();
    try {
        $config = require $path;
    } finally {
        ob_end_clean();
    }

    if (!is_array($config) || !isset($config['mysql']) || !is_array($config['mysql'])) {
        throw new RuntimeException('Configuración no válida.');
    }

    $mysql = $config['mysql'];
    foreach (['host', 'database', 'username', 'password'] as $field) {
        if (!isset($mysql[$field]) || !is_string($mysql[$field]) || $mysql[$field] === '') {
            throw new RuntimeException('Configuración incompleta.');
        }
    }
    foreach (['host', 'database'] as $field) {
        if (preg_match('/[;\x00-\x1f\x7f]/', $mysql[$field])) {
            throw new RuntimeException('Configuración no válida.');
        }
    }
    $port = filter_var($mysql['port'] ?? 3306, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 65535],
    ]);
    if ($port === false) {
        throw new RuntimeException('Puerto no válido.');
    }
    $mysql = [
        'host' => $mysql['host'],
        'port' => $port,
        'database' => $mysql['database'],
        'username' => $mysql['username'],
        'password' => $mysql['password'],
    ];

    $contacto = [];
    if (isset($config['contacto']) && is_array($config['contacto'])) {
        foreach (['nombre', 'direccion', 'telefono', 'whatsapp', 'correo', 'web'] as $field) {
            $value = $config['contacto'][$field] ?? null;
            if (is_string($value) && strlen($value) <= 500 && !preg_match('/[\x00-\x1f\x7f]/', $value)) {
                $contacto[$field] = trim($value);
            }
        }
    }

    return ['mysql' => $mysql, 'contacto' => $contacto, 'ia' => configuracionIa($config['ia'] ?? [])];
}

function configuracionIa(mixed $value): array
{
    $settings = is_array($value) ? $value : [];
    $key = $settings['api_key'] ?? '';
    $model = $settings['model'] ?? 'gpt-4.1-mini';
    if (!is_string($key) || strlen($key) > 512 || preg_match('/[\x00-\x20\x7f]/', $key)) $key = '';
    if (!is_string($model) || !preg_match('/\A[a-zA-Z0-9._-]{1,100}\z/', $model)) $model = 'gpt-4.1-mini';
    $limit = filter_var($settings['daily_request_limit'] ?? 100, FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1, 'max_range' => 10000]]);
    return [
        'enabled' => ($settings['enabled'] ?? false) === true && $key !== '',
        'api_key' => $key, 'model' => $model,
        'daily_request_limit' => $limit === false ? 100 : $limit,
    ];
}

function conectarMysql(array $config): PDO
{
    $dsn = 'mysql:host='.$config['host'].';port='.$config['port'].';dbname='.$config['database'].';charset=utf8mb4';

    return new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 3,
    ]);
}
