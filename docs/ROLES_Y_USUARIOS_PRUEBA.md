# Roles y cuentas de prueba de Lameri

`config/roles.php` mantiene los permisos funcionales del sistema actualizado: acceso al panel `/admin`, reportes, perfil, dashboard y acciones clínicas. El seeder instala los roles en la base nueva de Lameri. `admin` recibe todos los permisos operativos. Si ya existe un rol `laboratorista` en minúsculas, se respeta.

| Área | Admin | Recepción | Laboratorista |
| --- | --- | --- | --- |
| Usuarios y roles | Administrar | Sin acceso | Sin acceso |
| Clientes | Administrar | Crear, consultar, editar y gestionar según permisos | Crear, consultar, editar y gestionar según permisos |
| Órdenes | Administrar | Crear, consultar, pausar y reanudar | Gestionar, ingresar resultados, procesar muestras y finalizar |
| PDFs de órdenes finalizadas | Generar, ver, descargar y compartir | Generar, ver, descargar y compartir | Generar, ver, descargar y compartir |
| Cotizaciones | Acceder, generar PDF y compartir | Acceder, generar PDF y compartir | Acceder, generar PDF y compartir |
| Catálogos | Administrar | Consultar; administrar muestras | Consultar; gestionar valores de referencia |
| Dashboard | Todos los indicadores | Indicadores operativos, sin ingresos | Indicadores operativos, sin ingresos |
| Mi perfil | Cuenta, firma y sello propios; sello institucional | Cuenta propia | Cuenta, firma y sello propios |
| Bitácora | Todos los eventos, solo lectura | Sin acceso | Sin acceso |

La lista exacta de acciones está en `config/roles.php`. Se conservan los permisos heredados de reactivos aunque este proyecto no tenga ese recurso. Los permisos de impresión siguen asignados, pero los botones del Kanban permanecen ocultos mientras `IMPRESION_ETIQUETAS_HABILITADA=false`.

Los accesos se validan en páginas, rutas y acciones. Compartir PDF abre los enlaces de WhatsApp/correo existentes; la distribución del proyecto no envía mensajes a destinatarios reales. Las cuentas del portal `/expediente` se gestionan aparte y no acceden al administrador; consulta [PORTAL_MEDICOS.md](PORTAL_MEDICOS.md).

## Instalación y actualización de permisos

La instalación inicial y la cuenta del administrador se configuran en [README_HOSTINGER.md](../README_HOSTINGER.md). No incluye cuentas de personal ni contraseñas de producción. Usa `lameri_new` para actualizar el código de Lameri.

Si solo necesitas sincronizar roles y permisos, desde la raíz del proyecto por SSH:

```bash
php artisan db:seed --class=RolesPermisosSeeder --force
php artisan permission:cache-reset
```

Esto actualiza las asignaciones definidas por el sistema; revisa antes cualquier permiso que hayas personalizado. No ejecuta el seeder completo del catálogo ni crea pacientes u órdenes.

## Cuenta administrativa de prueba opcional

`php artisan lameri:usuarios-prueba` crea **únicamente `prueba.admin`**, conserva su contraseña si existe y sincroniza los tres roles. No crea Recepción ni Laboratorista de prueba. No ejecuta el seeder completo ni modifica el catálogo. Si encuentra una cuenta ajena con el mismo usuario o correo, se detiene antes de cambiar datos.

Consulta las contraseñas nuevas únicamente desde el almacenamiento privado del servidor:

```bash
cat storage/app/private/usuarios-prueba.json
```

El archivo tiene permisos 0600 y no se publica en Git ni en el ZIP. Usa la pestaña **Nombre de Usuario** del login. Firma y sello se encuentran en el menú de usuario → **Mi perfil**; se admiten PNG de hasta 2 MB según los permisos del rol.

## Retirar cuentas antiguas de personal de prueba

Si una instalación conserva cuentas de prueba de versiones anteriores:

```bash
php artisan lameri:eliminar-usuarios-prueba
```

El comando de limpieza se puede repetir y también retira sus entradas del archivo privado de credenciales. Las identifica por los pares usuario/correo de `prueba.lab`, `prueba.recepcion` y `prueba.laboratorista` con dominio `@lameri.test` o el dominio heredado `@oncosavi.test`, o por los nombres exactos `Prueba Lab`, `Prueba Recepcion`, `Prueba Recepción` y `Prueba Laboratorista`.

Conserva administradores, personal real, roles y permisos. Órdenes, pacientes, resultados, PDFs guardados, firmas y eventos de bitácora permanecen; las referencias al usuario de prueba retirado quedan vacías, sin atribuirlas a otro empleado. Retira sus sesiones y enlaces de restablecimiento de contraseña. No retira `prueba.admin`.

Los comandos `lameri:*` son los nombres vigentes; los alias `oncosavi:*` siguen disponibles por compatibilidad.

## Comprobaciones en desarrollo

`RolesAccionesTest` cubre los tres roles con usuarios temporales de una base aislada: URLs directas, llamadas a acciones ocultas, generación/descarga real del PDF, permisos de widgets, firma/sello propios y rechazo de la bitácora para Recepción y Laboratorista, incluso si conservan permisos antiguos. `RetirarUsuariosPruebaTest` cubre la limpieza repetible y la conservación de administradores, roles, permisos y registros clínicos.

Con dependencias de desarrollo instaladas, ejecuta en una base aislada:

```bash
DB_CONNECTION=sqlite DB_DATABASE=:memory: php -d memory_limit=512M vendor/bin/phpunit
```

Las comprobaciones descritas permiten validar cambios; no implican que se hayan ejecutado contra la base de Hostinger. Nunca uses la base operativa para PHPUnit.
