# Criterios visuales de Lameri

El panel de **LABORATORIO CLÍNICO MERINO** conserva las vistas y los flujos del sistema actualizado, con los logos y el diseño de Lameri. La identidad usa azul `#1E73BE`, naranja `#F6A623` y verde `#8BC34A`, con variantes para modo claro y oscuro. El panel está en `/admin` y el portal médico en `/expediente`.

Los colores de acciones se asignan mediante las APIs nativas de Filament:

| Uso | Color |
| --- | --- |
| Crear, guardar, editar, descargar y acción principal | `primary` |
| Ver, imprimir, volver, compartir por canales secundarios y configurar | `gray` |
| Activar, completar y confirmar éxito | `success` |
| Pausar, desactivar o advertir antes de una operación reversible | `warning` |
| Eliminar, cancelar, rechazar y comunicar errores | `danger` |
| Proceso en curso e información de servicios externos | `info` |
| Categorías, género, roles y estado inactivo | `gray` |

## Tema y componentes

- `config/laboratorio.php` centraliza identidad, contacto y logos. El correo se configura mediante `LABORATORIO_CORREO`; no se presupone una cuenta de correo institucional.
- `config/ui.php` define la escala primaria azul y la paleta de gráficos.
- `config/estados.php` mantiene los tonos semánticos de estados, independientes de los colores de marca.
- `AdminPanelProvider` registra los colores semánticos utilizados.
- `resources/views/partials/tema-lameri.blade.php` comparte tokens de superficie, borde, texto y énfasis, con variantes claras y oscuras.
- Chips, tablas de resultados y tarjetas de reportes utilizan los mismos tokens. Los componentes nativos conservan sus estados interactivos y deshabilitados.
- Los colores clínicos de recipientes conservan su significado. Los PDFs utilizan el membrete y el formato de Merino, manteniendo resultados, referencias, firmas y sellos.

El título de cierre de caja ofrece una etiqueta estática antes de `mount()`, porque Shield consulta títulos para los formularios de roles. Después de montar la página conserva el título dinámico y sus cálculos.

## Comprobar cambios visuales

La instalación y el empaquetado se describen en [README_HOSTINGER.md](../README_HOSTINGER.md). En desarrollo, desde la raíz del proyecto y con dependencias instaladas:

```bash
php artisan optimize:clear
php artisan view:cache
DB_CONNECTION=sqlite DB_DATABASE=:memory: php -d memory_limit=512M vendor/bin/phpunit
npm ci
npm run build
```

Estas instrucciones permiten repetir las comprobaciones; no son un informe de pruebas realizadas en el hosting. Usa una base aislada para PHPUnit, nunca la base operativa.

Al revisar el navegador, comprueba login, navegación, tablas, formularios, modales, Kanban, editor de matrices y portal médico tanto en claro como en oscuro. Revisa selección y retirada de exámenes antes de guardar, apertura del detalle de orden, impresión deshabilitada de un grupo vacío y lectura de referencias largas. Genera PDFs con datos ficticios y varias páginas para verificar encabezados, tablas y firmas dentro de los márgenes. Las pruebas automatizadas de contraste y permisos complementan esta revisión.
