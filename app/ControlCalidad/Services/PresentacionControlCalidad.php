<?php

namespace App\ControlCalidad\Services;

use Barryvdh\DomPDF\Facade\Pdf;

class PresentacionControlCalidad
{
    public function generar(string $consultaUrl)
    {
        $paginas = [
            ['01-resumen.png', '1. Resumen', 'El resumen reúne los últimos controles registrados y sus gráficos. Permite revisar la fecha, el área, el equipo y la cantidad de lecturas de cada registro. Las capturas de este documento muestran datos ficticios identificados con [DEMO].'],
            ['02-areas.png', '2. Mantenimiento: áreas y equipos', 'Cada área se vincula al nombre del equipo que se controla. El personal autorizado puede crear y editar las áreas necesarias, por ejemplo Química y Hematología. Esta configuración organiza las pruebas que aparecerán al registrar una medición.'],
            ['03-productos.png', '3. Mantenimiento: productos de control', 'Se registra el nombre del producto, el número de lote y su fecha de caducidad. Un producto puede tener varios lotes. Cada nivel de una prueba se vincula al producto y al lote que se utiliza para medirlo.'],
            ['04-pruebas.png', '4. Mantenimiento: pruebas', 'El catálogo reúne las pruebas de cada área con su unidad y sus niveles. Los nombres Bajo, Medio y Alto se ofrecen como sugerencias; el usuario también puede escribir nombres personalizados y configurar la cantidad de niveles que necesite.'],
            ['05-niveles.png', '5. Valores de control por nivel', 'Para cada nivel se selecciona el lote y se indican el mínimo y el máximo de su hoja de control. El valor esperado se calcula automáticamente como el punto medio: (mínimo + máximo) ÷ 2. Por ejemplo, entre 5,7 y 6,7 el esperado es 6,2.'],
            ['06-registro.png', '6. Registro de las mediciones', 'El laboratorista selecciona el área y transcribe los valores obtenidos en el equipo. Los niveles se presentan en columnas, junto con su unidad y sus límites. Se aceptan decimales con coma o punto y se pueden agregar observaciones. Al guardar se registra automáticamente la fecha, la hora y el usuario de la sesión.'],
            ['07-graficos.png', '7. Gráficos de seguimiento', 'Se seleccionan el área, la prueba y el período Diario, Semanal o Mensual. La franja sombreada muestra el rango de control y la X roja identifica una medición fuera de ese rango. El ejemplo muestra una medición fuera de límite y otra posterior dentro de rango, después de un ajuste simulado del equipo.'],
            ['08-ampliado.png', '8. Revisión en pantalla completa', 'El botón Ampliar permite examinar cualquier gráfico con más espacio. La vista conserva las fechas, la unidad, el valor esperado y los límites. Se vuelve a la pantalla anterior con Cerrar o con la tecla Escape.'],
            ['09-historial.png', '9. Historial de controles', 'El historial permite buscar registros y filtrar por área, laboratorista y fechas Desde y Hasta. El personal autorizado puede revisar un registro y, según sus permisos, editarlo o eliminarlo. Cada medición conserva los valores de control con los que fue registrada.'],
            ['10-exportacion.png', '10. Informes en PDF', 'El personal con permiso de exportación puede obtener un PDF de los datos y, cuando corresponda, incluir sus gráficos. Las exportaciones usan los filtros seleccionados y conservan la franja de control y las X rojas. Hay accesos desde Resumen, Mantenimiento, Registro, Gráficos e Historial, y desde cada gráfico.'],
            ['11-supervision.png', '11. Historial para supervisión', 'El enlace abre una tabla compacta con el número de registro, fecha, área, equipo y laboratorista. Ver registro muestra las lecturas completas. Los supervisores pueden filtrar, buscar y seleccionar varios registros para guardarlos juntos en PDF, con sus gráficos. Los datos se consultan sin modificarlos.'],
            ['12-supervision-graficos.png', '12. Gráficos para supervisión', 'El apartado Gráficos permite seleccionar el área y la prueba para revisar las mediciones de las fechas consultadas. La franja sombreada y las X rojas ayudan a identificar los resultados fuera del rango. Cada gráfico se puede ampliar y el PDF puede incluir la prueba seleccionada.'],
        ];
        foreach ($paginas as &$pagina) {
            $path = base_path('docs/control-calidad/capturas/'.$pagina[0]);
            if (! is_file($path)) {
                throw new \RuntimeException('Falta una captura de la presentación: '.$pagina[0]);
            }
            $pagina[0] = $path;
        }
        unset($pagina);

        return Pdf::setOptions(['isRemoteEnabled' => false, 'chroot' => base_path(), 'defaultFont' => 'DejaVu Sans', 'dpi' => 96])
            ->loadView('pdf.control-calidad.presentacion', compact('paginas', 'consultaUrl'))->setPaper('a4', 'landscape');
    }
}
