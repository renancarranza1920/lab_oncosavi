# ONCOSAVI · test para Hostinger

Aplicación completa para pruebas en hosting compartido con PHP y MySQL.
Esta rama incluye los estilos y scripts compilados; no necesita Docker, Node,
Redis, procesos de cola ni servicios adicionales para funcionar.

1. Usa PHP **8.3 o superior** y crea una base MySQL vacía para este sitio.
2. Sube el proyecto fuera de `public_html`, por ejemplo a la carpeta `oncosavi`.
3. Copia `.env.example` a `.env` y completa la URL temporal y los datos MySQL.
4. Desde la carpeta del proyecto ejecuta:

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --seed --force
php hostinger/publicar.php ../public_html
```

Abre `/admin/login` o `/expediente`. El seeder prepara los usuarios de prueba,
el acceso médico general, soporte, catálogo, pacientes, órdenes, resultados,
PDFs, firma y sellos de ejemplo. Repetir el seeder conserva los datos y contraseñas.

Los accesos se guardan en archivos privados:

```bash
cat storage/app/private/usuarios-prueba.json
cat storage/app/private/soporte-superadmin.json
cat storage/app/private/portal-medicos-general.json
```

Consulta [la guía de Hostinger](docs/ENTORNO_TEST_HOSTINGER.md) para configurar
`public_html`, actualizar el sitio y conservar los archivos. Esta rama usa una
base separada de la instalación real de Oracle; no se fusiona en `main`.
