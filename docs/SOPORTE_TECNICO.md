# Acceso de soporte técnico

La cuenta de soporte se crea únicamente al ejecutar `php artisan oncosavi:crear-superadmin`.
El despliegue o las migraciones no crean usuarios ni cambian las cuentas existentes.

La cuenta `soporte.superadmin` tiene los roles `admin` y `super_admin`. Es visible en
Usuarios y sus operaciones mantienen la auditoría normal, igual que las de otros
administradores. No hay cuentas ocultas, excepciones para borrar auditoría ni
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
