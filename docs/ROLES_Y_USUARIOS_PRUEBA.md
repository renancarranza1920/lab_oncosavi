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

## Instalación en test

El seeder de esta rama crea automáticamente las tres cuentas de prueba y conserva
sus contraseñas al repetirlo. Consulta [la guía de Hostinger](ENTORNO_TEST_HOSTINGER.md).

## Verificación

`RolesAccionesTest` comprueba los tres roles con usuarios temporales de una base aislada: URLs directas, llamadas a acciones ocultas, generación/descarga real del PDF, permisos de widgets, firma/sello propios, rechazo de rutas ajenas y bitácora filtrada. `RetirarUsuariosPruebaTest` verifica la limpieza repetible, la conservación de administradores, roles, permisos y registros clínicos, y la retirada de sesiones y credenciales de las cuentas eliminadas. Las pruebas usan una base aislada.

Para ejecutar la suite local, se requiere un límite PHP de 512 MB:

```bash
DB_CONNECTION=sqlite DB_DATABASE=:memory: php -d memory_limit=512M vendor/bin/phpunit
```
