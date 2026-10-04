# Acceso de soporte técnico

La cuenta de soporte se crea únicamente al ejecutar `php artisan oncosavi:crear-superadmin`.
El despliegue o las migraciones no crean usuarios ni cambian las cuentas existentes.

La cuenta `soporte.superadmin` tiene los roles `admin` y `super_admin`. Es visible en
Usuarios y sus operaciones mantienen una auditoría completa, con el detalle reservado
a soporte y al propietario autorizado. No hay cuentas ocultas, excepciones para borrar auditoría ni
credenciales preestablecidas en el repositorio. El rol adicional identifica soporte;
los permisos operativos siguen siendo los del rol `admin` existente.

En Oracle, después de actualizar el contenedor:

```bash
docker compose exec -T --user www-data app php artisan oncosavi:crear-superadmin
docker compose exec -T --user www-data app cat storage/app/private/soporte-superadmin.json
```

En otro alojamiento con PHP CLI:

```bash
php artisan oncosavi:crear-superadmin
cat storage/app/private/soporte-superadmin.json
```

La contraseña aleatoria se almacena como hash en la base de datos. El archivo de
acceso está en el almacenamiento privado, con permisos 600, y no se incluye en Git.
Se puede cambiar la contraseña desde Usuarios. Repetir el comando conserva la cuenta
existente, su contraseña y sus roles. Si el identificador ya pertenece a otra cuenta,
el comando se detiene sin reasignarla. Se pueden revocar los permisos o eliminar la
cuenta desde Usuarios mediante los permisos administrativos normales.

## Detalle reservado de los ajustes

Los eventos cuyo autor tiene el rol `super_admin` se muestran en la bitácora general
como **Ajuste de soporte técnico**, con fecha y usuario. El módulo, la acción original,
el modelo, los campos y los valores se conservan en `registros_soporte`, y no se
incluyen en los registros, búsquedas, filtros ni respuestas de la bitácora general.

En **Administración → Bitácora de soporte**, el soporte identificado puede revisar
los detalles. El propietario debe recibir un permiso **directo**, mediante consola:

```bash
php artisan oncosavi:autorizar-bitacora-soporte USUARIO_DEL_PROPIETARIO
```

Sustituye `USUARIO_DEL_PROPIETARIO` por el nombre de usuario administrador del dueño.
Asignar el permiso al rol `admin` no autoriza automáticamente a todos los administradores.
Para retirar la autorización directa, usa el mismo comando con `--revocar`.
El propietario autorizado y el soporte pueden consultar, pero no editar ni eliminar,
los registros desde esta pantalla. Contraseñas, tokens y claves no se almacenan en
los campos del detalle de auditoría.

Para reservar eventos anteriores de usuarios que actualmente tienen el rol
`super_admin`, ejecuta después de migrar:

```bash
php artisan oncosavi:proteger-bitacora-soporte
```

El comando conserva los IDs, fechas y el autor del evento general. Copia primero
el detalle original a la auditoría privada y reemplaza el detalle general por el
aviso genérico dentro de la misma transacción. Repetirlo no duplica registros ni
cambia los datos clínicos. La migración de auditoría crea una tabla adicional y
conserva esa tabla si se revierte: no se elimina el historial privado.
