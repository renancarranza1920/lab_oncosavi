# Criterios visuales del panel ONCOSAVI

El panel conserva su estructura, sus iconos de navegación y la identidad marino/celeste. Los colores de acciones se asignan mediante las APIs nativas de Filament.

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

- `config/ui.php` define la escala primaria completa; evita generar tonos oscuros ilegibles a partir de un único marino.
- `config/estados.php` mantiene los tonos semánticos. Warning e info tienen suficiente contraste con texto blanco.
- `AdminPanelProvider` registra sólo colores semánticos utilizados, sin alias de colores sin uso.
- `partials/tema-oncosavi` comparte tokens de superficie, borde, texto y énfasis para los componentes propios, con variantes claras y oscuras.
- Se eliminaron overrides globales de clases Tailwind y estilos `!important` sobre botones, badges y notificaciones. Los componentes nativos conservan sus estados interactivos.
- Dos ajustes específicos cubren limitaciones medidas de Filament 3: hover de botones rellenos en oscuro e iconos neutros demasiado claros. Los estados deshabilitados conservan su tratamiento nativo.
- Chips, tablas de resultados y tarjetas de reportes usan los mismos tokens. Los KPI emplean Heroicons y una paleta sobria.
- Los colores clínicos de recipientes y los documentos PDF conservan sus estilos específicos.

El título de cierre de caja ofrece una etiqueta estática antes de `mount()`, porque Shield consulta títulos para los formularios de roles. Después de montar la página conserva el título dinámico y sus cálculos originales.

## Validación

Con el entorno preparado y desde la raíz del repositorio:

```bash
composer install --no-interaction --prefer-source
php artisan optimize:clear
php artisan view:cache
DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test
npm ci
npm run build
```

En el entorno de nube, ejecutar antes `source /workspace/toolchain/activate.sh`. La opción `--prefer-source` evita ZIP de GitHub bloqueados por la política de red, respetando `composer.lock`.

La revisión incluyó 21 páginas principales, 27 formularios/vistas y el editor de matrices en Chromium, tanto en claro como en oscuro. Se comprobaron selección, carga y retirada de exámenes sin guardar el formulario, apertura del modal de orden y deshabilitación de impresión para un grupo vacío. Las comprobaciones utilizaron una copia temporal de SQLite con datos de prueba.

Se midieron 198 combinaciones de componentes/temas/estados: al menos 4,5:1 para texto y 3:1 para iconos interactivos. Las pruebas de regresión cubren contraste de paletas, significado de estados, renderizado de enlaces y eliminación de resultados, y compatibilidad del título con Shield.
