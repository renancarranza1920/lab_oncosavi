<?php

namespace App\Console\Commands;

use App\Services\QzFirmaService;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ConfigurarQz extends Command
{
    protected $signature = 'lameri:qz-configurar
        {--certificado= : Ruta al certificado público PEM}
        {--clave= : Ruta a la clave privada PEM correspondiente}
        {--desde-js= : Respaldo privado del sign-message.js anterior}
        {--desde-vista= : Respaldo de la vista anterior que contiene su certificado}';

    protected $description = 'Importa el par existente de QZ y lo conserva en el almacenamiento privado del servidor.';

    private const JS_STRING = <<<'REGEX'
(?:"(?:[^"\\\r\n]|\\["\\/bfnrt]|\\u[0-9a-fA-F]{4})*"|'(?:[^'\\\r\n]|\\['"\\/bfnrt]|\\u[0-9a-fA-F]{4})*')
REGEX;

    public function handle(QzFirmaService $servicio): int
    {
        try {
            [$certificado, $clave] = $this->leerPar();
            $metadatos = $servicio->configurar($certificado, $clave);

            $this->info('QZ configurado. El certificado y la clave corresponden; no se generó una identidad nueva.');
            if (! empty($metadatos['fingerprint_sha256'])) {
                $this->line('Huella SHA-256: '.$metadatos['fingerprint_sha256']);
            }
            if (! empty($metadatos['valid_until'])) {
                $this->line('Válido hasta: '.$metadatos['valid_until']);
            }
            $this->line('Configuración privada: '.($metadatos['ruta_configuracion'] ?? storage_path('app/private/qz/configuracion.json')));

            return self::SUCCESS;
        } catch (ValidationException $exception) {
            $this->error(implode(' ', array_merge(...array_values($exception->errors()))));
        } catch (\LogicException|RuntimeException $exception) {
            $this->error($exception->getMessage());
        }

        return self::FAILURE;
    }

    private function leerPar(): array
    {
        $rutaCertificado = $this->option('certificado');
        $rutaClave = $this->option('clave');
        $rutaJs = $this->option('desde-js');
        $rutaVista = $this->option('desde-vista');

        if ($rutaCertificado || $rutaClave) {
            if (! $rutaCertificado || ! $rutaClave || $rutaJs || $rutaVista) {
                throw new RuntimeException('Use --certificado y --clave juntos, sin combinarlos con --desde-js ni --desde-vista.');
            }

            return [$this->leerArchivo($rutaCertificado), $this->leerArchivo($rutaClave)];
        }

        if (! $rutaJs) {
            throw new RuntimeException('Indique --certificado y --clave, o importe el respaldo con --desde-js y, opcionalmente, --desde-vista.');
        }

        $claves = $this->extraerPems($this->leerArchivo($rutaJs), clave: true);
        if (count($claves) !== 1) {
            throw new RuntimeException('El respaldo JavaScript debe contener una única clave privada literal. También puede utilizar --certificado y --clave con los archivos PEM originales.');
        }

        $clave = $claves[0];
        $recursoClave = @openssl_pkey_get_private($clave);
        $detalles = $recursoClave ? openssl_pkey_get_details($recursoClave) : false;
        if (! $detalles || $detalles['type'] !== OPENSSL_KEYTYPE_RSA || $detalles['bits'] < 2048) {
            throw new RuntimeException('La clave del respaldo no es una clave privada RSA válida de al menos 2048 bits, sin contraseña.');
        }

        $candidatos = [];
        if ($rutaVista) {
            $candidatos = $this->extraerPems($this->leerArchivo($rutaVista));
        }
        foreach (glob(resource_path('qz/*.crt')) ?: [] as $ruta) {
            $candidatos[] = $this->leerArchivo($ruta);
        }

        $coincidentes = [];
        $coincideFueraDeVigencia = false;
        foreach ($candidatos as $candidato) {
            $certificado = @openssl_x509_read($candidato);
            if (! $certificado || ! @openssl_x509_check_private_key($certificado, $recursoClave)) {
                continue;
            }
            $datos = openssl_x509_parse($certificado);
            if (! $datos || ($datos['validFrom_time_t'] ?? PHP_INT_MAX) > time() || ($datos['validTo_time_t'] ?? 0) <= time()) {
                $coincideFueraDeVigencia = true;
                continue;
            }
            $huella = openssl_x509_fingerprint($certificado, 'sha256');
            if ($huella) {
                $coincidentes[$huella] = $candidato;
            }
        }

        if (count($coincidentes) > 1) {
            throw new RuntimeException('Hay varios certificados vigentes para esa clave. Elija el certificado que utiliza su equipo mediante --certificado y --clave.');
        }
        if (! $coincidentes) {
            throw new RuntimeException($coincideFueraDeVigencia
                ? 'El certificado correspondiente a esa clave no está vigente. Importe un par vigente mediante --certificado y --clave.'
                : 'No se encontró un certificado que corresponda a la clave del respaldo. Use --desde-vista con su vista anterior o importe los PEM originales mediante --certificado y --clave.');
        }

        return [reset($coincidentes), $clave];
    }

    private function leerArchivo(string $ruta): string
    {
        if (! is_file($ruta) || ! is_readable($ruta) || filesize($ruta) > 1024 * 1024) {
            throw new RuntimeException('El archivo indicado no existe, no es legible o supera 1 MB.');
        }
        $contenido = @file_get_contents($ruta);
        if ($contenido === false || $contenido === '') {
            throw new RuntimeException('No se pudo leer el archivo indicado o está vacío.');
        }

        return $contenido;
    }

    private function extraerPems(string $fuente, bool $clave = false): array
    {
        // Solo se decodifican literales: nunca se ejecuta el JavaScript del respaldo.
        $literal = self::JS_STRING;
        preg_match_all('~'.$literal.'(?:\s*\+\s*'.$literal.')*~', $fuente, $expresiones);
        $resultado = [];
        $patronPem = $clave
            ? '~-----BEGIN (RSA PRIVATE KEY|PRIVATE KEY)-----\s+[A-Za-z0-9+/=\s]+-----END \1-----~'
            : '~-----BEGIN CERTIFICATE-----\s+[A-Za-z0-9+/=\s]+-----END CERTIFICATE-----~';

        foreach ($expresiones[0] as $expresion) {
            preg_match_all('~'.$literal.'~', $expresion, $tokens);
            $contenido = '';
            foreach ($tokens[0] as $token) {
                $contenido .= $this->decodificarLiteral($token);
            }
            preg_match_all($patronPem, $contenido, $pems);
            foreach ($pems[0] as $pem) {
                $normalizado = trim($pem)."\n";
                $resultado[hash('sha256', $normalizado)] = $normalizado;
            }
        }

        return array_values($resultado);
    }

    private function decodificarLiteral(string $token): string
    {
        try {
            if ($token[0] === '"') {
                return json_decode($token, true, 512, JSON_THROW_ON_ERROR);
            }

            preg_match_all('~\\\\(?:u[0-9a-fA-F]{4}|[\'"\\\\/bfnrt])|[^\\\\]+~', substr($token, 1, -1), $partes);
            $resultado = '';
            foreach ($partes[0] as $parte) {
                $resultado .= $parte[0] !== '\\'
                    ? $parte
                    : ($parte === "\\'" ? "'" : json_decode('"'.$parte.'"', true, 512, JSON_THROW_ON_ERROR));
            }

            return $resultado;
        } catch (\JsonException) {
            throw new RuntimeException('El respaldo contiene un literal JavaScript que no se puede importar. Use los archivos PEM originales.');
        }
    }
}
