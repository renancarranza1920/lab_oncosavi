<?php

namespace App\Services;

use App\Exceptions\QzFirmaException;

class QzFirmaService
{
    public function configurar(string $certificado, string $clave): array
    {
        $certificado = $this->normalizarCertificado($certificado);
        $metadatos = $this->validar($certificado, $clave);
        if (! openssl_pkey_export($metadatos['clave'], $clave)) {
            throw new QzFirmaException('No se pudo preparar la clave privada de QZ.', 503);
        }
        $directorio = dirname($this->ruta());

        if (! is_dir($directorio) && ! @mkdir($directorio, 0700, true) && ! is_dir($directorio)) {
            throw new QzFirmaException('No se pudo crear la carpeta privada de QZ. Revise sus permisos.', 503);
        }

        @chmod($directorio, 0700);
        $temporal = tempnam($directorio, '.qz-');
        if ($temporal === false) {
            throw new QzFirmaException('No se pudo preparar la configuración privada de QZ.', 503);
        }

        try {
            $contenido = json_encode(['certificado' => $certificado, 'clave_privada' => $clave], JSON_THROW_ON_ERROR);
            if (! @chmod($temporal, 0600)
                || file_put_contents($temporal, $contenido, LOCK_EX) === false
                || ! @rename($temporal, $this->ruta())) {
                throw new QzFirmaException('No se pudo guardar la configuración privada de QZ.', 503);
            }
        } finally {
            if (is_file($temporal)) {
                @unlink($temporal);
            }
        }

        return $this->metadatosPublicos($metadatos) + ['ruta_configuracion' => $this->ruta()];
    }

    public function certificado(): string
    {
        return $this->cargar()['certificado'];
    }

    public function certificadoPublico(): array
    {
        $configuracion = $this->cargar();

        return ['certificado' => $configuracion['certificado']] + $this->metadatosPublicos($configuracion);
    }

    public function estado(): array
    {
        return $this->metadatosPublicos($this->cargar());
    }

    public function firmar(string $mensaje, ?string $fingerprintEsperado = null): string
    {
        $configuracion = $this->cargar();
        if ($fingerprintEsperado !== null
            && ! hash_equals($configuracion['fingerprint_sha256'], strtolower(str_replace(':', '', $fingerprintEsperado)))) {
            throw new QzFirmaException('El certificado de QZ cambió. Recargue la página antes de imprimir.', 409);
        }

        if (! openssl_sign($mensaje, $firma, $configuracion['clave'], OPENSSL_ALGO_SHA512)) {
            throw new QzFirmaException('No se pudo firmar el mensaje de QZ.', 503);
        }

        return base64_encode($firma);
    }

    private function ruta(): string
    {
        return storage_path('app/private/qz/configuracion.json');
    }

    private function cargar(): array
    {
        if (! is_file($this->ruta()) || ! is_readable($this->ruta())) {
            throw new QzFirmaException('Configure la firma de QZ con php artisan lameri:qz-configurar antes de imprimir.', 503);
        }

        $configuracion = json_decode(file_get_contents($this->ruta()), true);
        if (! is_array($configuracion)
            || ! is_string($configuracion['certificado'] ?? null)
            || ! is_string($configuracion['clave_privada'] ?? null)) {
            throw new QzFirmaException('La configuración privada de QZ no es válida. Vuelva a configurar el certificado y su clave.', 503);
        }

        $certificado = $this->normalizarCertificado($configuracion['certificado']);

        return ['certificado' => $certificado]
            + $this->validar($certificado, $configuracion['clave_privada']);
    }

    private function normalizarCertificado(string $contenido): string
    {
        // Un PEM combinado puede incluir también una clave. Solo los bloques
        // públicos de certificados, incluida su cadena, se entregan al navegador.
        preg_match_all('~-----BEGIN CERTIFICATE-----[A-Za-z0-9+/=\s]+-----END CERTIFICATE-----~', $contenido, $bloques);
        $certificados = [];
        foreach ($bloques[0] as $bloque) {
            if (! @openssl_x509_export($bloque, $publico)) {
                throw new QzFirmaException('El certificado de QZ no tiene un formato PEM válido.', 503);
            }
            $certificados[] = $publico;
        }

        if (! $certificados) {
            throw new QzFirmaException('El certificado de QZ no tiene un formato PEM válido.', 503);
        }

        return implode('', $certificados);
    }

    private function validar(string $certificado, string $clave): array
    {
        if (! extension_loaded('openssl')) {
            throw new QzFirmaException('PHP necesita OpenSSL para firmar los mensajes de QZ.', 503);
        }

        $cert = @openssl_x509_read($certificado);
        $privada = @openssl_pkey_get_private($clave);
        if ($cert === false || $privada === false) {
            throw new QzFirmaException('El certificado o la clave privada de QZ no tienen un formato PEM válido.', 503);
        }

        $detalles = openssl_pkey_get_details($privada);
        if (! $detalles || $detalles['type'] !== OPENSSL_KEYTYPE_RSA || $detalles['bits'] < 2048) {
            throw new QzFirmaException('QZ requiere una clave RSA de al menos 2048 bits.', 503);
        }

        if (! openssl_x509_check_private_key($cert, $privada)) {
            throw new QzFirmaException('La clave privada no corresponde al certificado de QZ.', 503);
        }

        $datos = openssl_x509_parse($cert);
        if (! $datos || ($datos['validFrom_time_t'] ?? PHP_INT_MAX) > time()
            || ($datos['validTo_time_t'] ?? 0) <= time()) {
            throw new QzFirmaException('El certificado de QZ está caducado o todavía no es válido.', 503);
        }

        return [
            'clave' => $privada,
            'fingerprint_sha256' => openssl_x509_fingerprint($cert, 'sha256'),
            'valid_until' => gmdate(DATE_ATOM, $datos['validTo_time_t']),
            'subject' => $datos['subject']['CN'] ?? '',
        ];
    }

    private function metadatosPublicos(array $configuracion): array
    {
        return array_intersect_key($configuracion, array_flip(['fingerprint_sha256', 'valid_until', 'subject']));
    }
}
