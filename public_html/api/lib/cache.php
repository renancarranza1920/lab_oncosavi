<?php

declare(strict_types=1);

namespace LameriPublica;

if (!defined('LAMERI_PUBLIC_API')) {
    http_response_code(404);
    exit;
}

final class CacheCatalogo
{
    private const TTL = 60;
    private string $path;

    public function __construct(private string $directory, array $mysql)
    {
        // Una conexión nueva no puede heredar precios de otra base o credencial.
        $key = hash('sha256', json_encode($mysql, JSON_INVALID_UTF8_SUBSTITUTE));
        $this->path = $directory.'/catalogo-v1-'.$key.'.json';
    }

    public function leer(): ?array
    {
        if (!is_file($this->path) || !is_readable($this->path) || @filesize($this->path) > 2097152) {
            return null;
        }
        $content = @file_get_contents($this->path);
        $cache = is_string($content) ? json_decode($content, true) : null;
        $now = time();
        if (!is_array($cache) || !isset($cache['creado_en'], $cache['catalogo'])
            || !is_int($cache['creado_en']) || $cache['creado_en'] > $now
            || $now - $cache['creado_en'] >= self::TTL || !is_array($cache['catalogo'])) {
            return null;
        }
        $catalogo = $cache['catalogo'];
        if (($catalogo['moneda'] ?? null) !== 'USD' || !is_string($catalogo['consultado_en'] ?? null)
            || !is_array($catalogo['examens'] ?? null) || !is_array($catalogo['perfiles'] ?? null)
            || !array_is_list($catalogo['examens']) || !array_is_list($catalogo['perfiles'])) {
            return null;
        }

        // Validar y filtrar también las filas; una caché corrupta obliga a leer la BD.
        $examens = [];
        foreach ($catalogo['examens'] as $examen) {
            if (!$this->registroValido($examen) || !is_string($examen['categoria'] ?? null) || trim($examen['categoria']) === '') {
                return null;
            }
            $examens[] = [
                'id' => $examen['id'], 'nombre' => $examen['nombre'],
                'precio' => $examen['precio'], 'categoria' => $examen['categoria'],
            ];
        }
        $perfiles = [];
        foreach ($catalogo['perfiles'] as $perfil) {
            if (!$this->registroValido($perfil) || !is_array($perfil['incluidos'] ?? null)
                || !array_is_list($perfil['incluidos']) || count($perfil['incluidos']) < 2) {
                return null;
            }
            foreach ($perfil['incluidos'] as $nombre) {
                if (!is_string($nombre) || trim($nombre) === '') {
                    return null;
                }
            }
            $perfiles[] = [
                'id' => $perfil['id'], 'nombre' => $perfil['nombre'],
                'precio' => $perfil['precio'], 'incluidos' => $perfil['incluidos'],
            ];
        }

        return [
            'moneda' => $catalogo['moneda'],
            'consultado_en' => $catalogo['consultado_en'],
            'examens' => $examens,
            'perfiles' => $perfiles,
        ];
    }

    private function registroValido(mixed $registro): bool
    {
        return is_array($registro) && is_int($registro['id'] ?? null) && $registro['id'] > 0
            && is_string($registro['nombre'] ?? null) && trim($registro['nombre']) !== ''
            && is_string($registro['precio'] ?? null)
            && preg_match('/^[0-9]+\.[0-9]{2}$/D', $registro['precio']) === 1;
    }

    public function guardar(array $catalogo): void
    {
        // Caché opcional: la respuesta fresca funciona aunque el hosting no permita escribir.
        if (!is_dir($this->directory) && !@mkdir($this->directory, 0700, true) && !is_dir($this->directory)) {
            return;
        }
        if (!is_writable($this->directory)) {
            return;
        }
        $content = json_encode(['creado_en' => time(), 'catalogo' => $catalogo], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($content === false || strlen($content) > 2097152) {
            return;
        }
        $temporary = @tempnam($this->directory, '.catalogo-');
        if ($temporary === false) {
            return;
        }
        if (dirname($temporary) !== $this->directory || !@chmod($temporary, 0600)
            || @file_put_contents($temporary, $content, LOCK_EX) === false || !@rename($temporary, $this->path)) {
            @unlink($temporary);
        }
    }
}
