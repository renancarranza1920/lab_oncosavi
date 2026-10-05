<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class ImagenPdf
{
    public static function desdeDiscoPublico(?string $path, int $ancho = 420, int $alto = 260): ?string
    {
        if (!$path || !Storage::disk('public')->exists($path)) {
            return null;
        }

        // Leer el archivo actual: no conservar imágenes ni ausencias de una generación anterior.
        return self::desdeContenido(Storage::disk('public')->get($path), $ancho, $alto);
    }

    public static function desdeArchivo(?string $path, int $ancho = 900, int $alto = 900): ?string
    {
        if (!$path) {
            return null;
        }
        clearstatcache(true, $path);
        if (!is_file($path) || !is_readable($path)) {
            return null;
        }

        return self::desdeContenido(file_get_contents($path), $ancho, $alto);
    }

    private static function desdeContenido(string $contenido, int $ancho, int $alto): ?string
    {
        $tamano = @getimagesizefromstring($contenido);
        if (!$tamano || !in_array($tamano[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_GIF, IMAGETYPE_WEBP], true)) {
            return null;
        }
        $mime = image_type_to_mime_type($tamano[2]);

        if (extension_loaded('gd') && ($tamano[0] > $ancho || $tamano[1] > $alto)) {
            $origen = @imagecreatefromstring($contenido);
            if ($origen) {
                $escala = min($ancho / $tamano[0], $alto / $tamano[1], 1);
                $destino = imagecreatetruecolor(max(1, (int) round($tamano[0] * $escala)), max(1, (int) round($tamano[1] * $escala)));
                imagealphablending($destino, false);
                imagesavealpha($destino, true);
                imagefill($destino, 0, 0, imagecolorallocatealpha($destino, 255, 255, 255, 127));
                imagecopyresampled($destino, $origen, 0, 0, 0, 0, imagesx($destino), imagesy($destino), $tamano[0], $tamano[1]);
                ob_start();
                imagepng($destino, null, 6);
                $contenido = ob_get_clean();
                $mime = 'image/png';
                imagedestroy($origen);
                imagedestroy($destino);
            }
        }

        // Incorporar los bytes evita depender del chroot o de enlaces al volumen de storage.
        return 'data:'.$mime.';base64,'.base64_encode($contenido);
    }
}
