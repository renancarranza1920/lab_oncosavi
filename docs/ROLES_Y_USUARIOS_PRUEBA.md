# Roles y cuentas de prueba

La base de asignaciones es la lista compartida de la BD existente. `config/roles.php` conserva sus acciones por nombre, agrega el acceso al panel y las nuevas acciones de reportes, perfil y dashboard. Admin recibe todos los permisos. Se respeta un rol existente `laboratorista` en minúsculas.

| Área | Admin | Recepción | Laboratorista |
|---|---|---|---|
| Usuarios y roles | Administrar | Sin acceso | Sin acceso |
| Clientes | Administrar | Acciones de su configuración anterior | Acciones de su configuración anterior |
| Órdenes | Administrar | Crear, consultar, pausar y reanudar | Acciones de su configuración anterior, incluidos resultados y finalización |
| PDFs de órdenes finalizadas | Generar, ver, descargar y compartir | Generar, ver, descargar y compartir | Generar, ver, descargar y compartir |
| Cotizaciones | Acceder, generar PDF y compartir | Acceder, generar PDF y compartir | Acceder, generar PDF y compartir |
| Catálogos | Administrar | Consultar; administrar muestras | Consultar; gestionar valores de referencia |
| Dashboard | Todos los indicadores | Indicadores operativos, sin ingresos | Indicadores operativos, sin ingresos |
| Mi perfil | Cuenta, firma y sello propios | Cuenta propia | Cuenta, firma y sello propios |
| Bitácora | Todos los eventos, solo lectura | Sin acceso | Eventos de resultados, solo lectura |

Se conservan también los permisos heredados de reactivos aunque este checkout no tenga ese recurso. Los permisos de impresión siguen asignados, pero los botones del Kanban continúan ocultos mediante la configuración de impresión existente.

Los accesos se validan en las páginas, rutas y acciones. Compartir PDF abre los enlaces existentes de WhatsApp/correo; estas pruebas no envían mensajes a destinatarios reales.

## Actualizar Oracle sin borrar volúmenes

Desde `~/lab_oncosavi`, con el repositorio limpio y en `main`:

```bash
git pull --ff-only origin main
docker compose build app
docker compose up -d --no-deps --force-recreate app
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan oncosavi:usuarios-prueba
docker compose exec app php artisan permission:cache-reset
```

El comando crea `prueba.admin`, `prueba.recepcion` y `prueba.laboratorista`, sincroniza permisos de los roles y conserva contraseñas de cuentas ya existentes. No ejecuta el seeder completo ni modifica catálogos, órdenes o pacientes. Si encuentra una cuenta ajena con el mismo usuario o correo, se detiene antes de cambiar datos.

Consultar las contraseñas nuevas únicamente en la terminal del servidor:

```bash
docker compose exec app cat storage/app/private/usuarios-prueba.json
```

El archivo permanece en el volumen `laravel_storage`, con permisos 0600, y no se publica en Git. Usar la pestaña **Nombre de Usuario** del login. Firma y sello se encuentran en el menú de usuario → **Mi perfil**; archivos PNG de hasta 2 MB.

Si solo se necesitan actualizar permisos, sin crear las cuentas:

```bash
docker compose exec app php artisan db:seed --class=RolesPermisosSeeder --force
```

## Verificación

`RolesAccionesTest` comprueba los tres roles, URLs directas, llamadas a acciones ocultas, generación/descarga real del PDF, permisos de widgets, firma/sello propios, rechazo de rutas ajenas, bitácora filtrada y cuentas repetibles. La verificación en navegador se realiza en una base local aislada con las tres cuentas. No implica creación de usuarios ni despliegue en Oracle.

Para ejecutar la suite local, se requiere el límite PHP de 512 MB que ya configura `docker/php.ini`:

```bash
DB_CONNECTION=sqlite DB_DATABASE=:memory: php -d memory_limit=512M vendor/bin/phpunit
```
