# Acceso de soporte técnico

La cuenta de soporte se crea únicamente al ejecutar `php artisan oncosavi:crear-superadmin`.
El despliegue o las migraciones no crean usuarios ni cambian las cuentas existentes.

La cuenta de soporte (por ejemplo, `soporte.admin` o `soporte.superadmin`) se identifica
por su rol `super_admin`, no por su nombre de usuario. Su cuenta y su rol se muestran
en Usuarios y Roles únicamente al soporte y al propietario autorizado. Los demás
usuarios no pueden encontrarlos mediante listas, búsquedas ni URLs de edición.
Sus operaciones mantienen una auditoría completa en la sección privada. No se borran
registros ni existen credenciales preestablecidas en el repositorio. Los permisos
operativos siguen siendo los del rol `admin` existente.

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
el comando se detiene sin reasignarla. El propietario autorizado puede modificar los permisos o eliminar la
cuenta desde Usuarios.

## Detalle reservado de los ajustes

La bitácora general y la bitácora de soporte requieren el rol `admin`; Recepción
y Laboratorista no tienen acceso. La cuenta de soporte conserva sus roles
`admin` y `super_admin`.

La bitácora general excluye los eventos de soporte y los cambios sobre su cuenta o
su rol. También excluye los eventos antiguos aún sin archivar y el aviso genérico
«Ajuste de soporte técnico». No expone estos registros mediante búsquedas, filtros,
contadores ni detalles. El módulo, la acción original, el autor, la fecha, el modelo,
los campos y los valores se conservan en `registros_soporte`.

El acceso al inicio de sesión y la autoría de resultados clínicos siguen usando los
usuarios reales. No se cambia ni se anonimiza al responsable de un resultado o informe.

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

Para archivar también los eventos anteriores de usuarios que actualmente tienen el
rol de soporte y los cambios sobre sus cuentas o roles, ejecuta después de actualizar:

```bash
php artisan oncosavi:proteger-bitacora-soporte
```

El comando conserva los IDs, fechas y el autor del evento general. Copia primero
el detalle original a la auditoría privada y reemplaza el detalle general por el
aviso genérico dentro de la misma transacción. Repetirlo no duplica registros ni
cambia los datos clínicos. La migración de auditoría crea una tabla adicional y
conserva esa tabla si se revierte: no se elimina el historial privado.

La cuenta y el rol de soporte solo se muestran a soporte y al propietario autorizado;
este último conserva acceso para revisarlos, editarlos o eliminarlos. Otros
administradores no pueden asignarse `super_admin`, renombrar un rol normal a ese nombre ni usar la
eliminación masiva de usuarios o roles. Conservan la gestión individual de usuarios
y roles ordinarios. Esto evita obtener el detalle privado tomando la cuenta o el rol
de soporte. El propietario debe usar la autorización directa anterior para gestionar
estas operaciones desde su cuenta habitual.

También se reservan las variantes de nombre `Super Admin`, `super-admin` y
`superadmin`, sin distinguir mayúsculas ni espacios exteriores. Cambiar el apodo
de un usuario ordinario a `soporte.admin` no le concede permisos ni privacidad.
