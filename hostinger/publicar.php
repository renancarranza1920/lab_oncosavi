<?php

// Ejecutar por SSH: php hostinger/publicar.php ../public_html
// El proyecto queda fuera de public_html; solo se copian los archivos de public.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

try {
    $proyecto = dirname(__DIR__);
    $destino = $argv[1] ?? '../public_html';
    if (!is_dir($destino) && !mkdir($destino, 0755, true)) {
        throw new RuntimeException('No se pudo crear la carpeta pública.');
    }
    $destino = realpath($destino);
    if ($destino === $proyecto || str_starts_with($destino.'/', $proyecto.'/')) {
        throw new RuntimeException('La carpeta pública debe quedar fuera del proyecto.');
    }
    if (!is_file($proyecto.'/public/build/manifest.json')) {
        throw new RuntimeException('Faltan los estilos compilados de la rama test.');
    }
    $indice = $destino.'/index.php';
    if (is_file($indice) && !str_contains(file_get_contents($indice), 'ONCOSAVI_PUBLIC_HOSTINGER')) {
        throw new RuntimeException('Ya hay un index.php de otro sitio. Use una carpeta pública nueva para pruebas.');
    }
    $almacen = $proyecto.'/storage/app/public';
    if (!is_dir($almacen) && !mkdir($almacen, 0755, true)) {
        throw new RuntimeException('No se pudo crear la carpeta de archivos.');
    }
    $enlace = $destino.'/storage';
    if (is_link($enlace)) {
        if (realpath($enlace) !== realpath($almacen)) {
            throw new RuntimeException('El enlace storage pertenece a otra instalación.');
        }
    } elseif (file_exists($enlace) || !symlink($almacen, $enlace)) {
        throw new RuntimeException('No se pudo enlazar storage. Configure la raíz del sitio en la carpeta public del proyecto.');
    }

    $archivos = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($proyecto.'/public', FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($archivos as $archivo) {
        $relativo = substr($archivo->getPathname(), strlen($proyecto.'/public/'));
        if ($archivo->isLink() || $relativo === 'index.php' || $relativo === 'hot'
            || basename($relativo) === '.gitignore' || str_starts_with($relativo, 'storage/')) {
            continue;
        }
        $ruta = $destino.'/'.$relativo;
        if ($archivo->isDir()) {
            if (!is_dir($ruta) && !mkdir($ruta, 0755, true)) {
                throw new RuntimeException('No se pudo crear una carpeta de recursos.');
            }
        } elseif (!copy($archivo->getPathname(), $ruta)) {
            throw new RuntimeException('No se pudo copiar un recurso público.');
        }
    }
    $base = var_export($proyecto, true);
    $contenido = "<?php\n// ONCOSAVI_PUBLIC_HOSTINGER: generado por hostinger/publicar.php\n"
        ."define('LARAVEL_START', microtime(true));\n\$base = {$base};\n"
        ."if (file_exists(\$maintenance = \$base.'/storage/framework/maintenance.php')) { require \$maintenance; }\n"
        ."require \$base.'/vendor/autoload.php';\n"
        ."\$app = require_once \$base.'/bootstrap/app.php';\n"
        ."\$app->usePublicPath(__DIR__);\n"
        ."\$app->handleRequest(Illuminate\\Http\\Request::capture());\n";
    if (file_put_contents($indice, $contenido) === false) {
        throw new RuntimeException('No se pudo preparar index.php.');
    }
    echo "Sitio publicado en {$destino}. El .env, las contraseñas y el código quedan fuera de la carpeta pública.\n";
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage()."\n");
    exit(1);
}
