<!doctype html><html lang="es"><head><meta charset="utf-8"><style>
@page{margin:28px 35px 30px}body{font:12px/1.55 'DejaVu Sans';color:#172435}h1{font-size:25px;line-height:1.2;color:#184b64;margin:8px 0 18px}h2{font-size:20px;line-height:1.2;color:#184b64;margin:0 0 10px}.eyebrow{font-size:12px;letter-spacing:1px;color:#526377}.cover{padding:55px 36px}.cover p{max-width:870px;font-size:15px}.access{background:#eaf5f6;border-left:5px solid #23647b;padding:18px;margin:25px 0}.access a{color:#184b64;word-wrap:break-word;font-size:12px}.page{page-break-before:always}.page p{margin:0 0 15px}.capture{display:block;width:100%;max-height:475px;object-fit:contain;border:1px solid #dbe3ea}.footer{position:fixed;bottom:-18px;font-size:9px;color:#526377}.note{font-size:12px;color:#526377}
</style></head><body>
<div class="footer">ONCOSAVI · Control de Calidad Interno · Presentación para supervisión · Capturas reales con datos de demostración</div>
<section class="cover">
    <div class="eyebrow">LABORATORIO ONCOSAVI</div>
    <h1>Control de Calidad Interno</h1>
    <p>Guía visual para la revisión de la implementación</p>
    <p>Este documento presenta las cinco pantallas del módulo y el ciclo de trabajo: configurar áreas, productos y pruebas; registrar las mediciones del equipo; consultar gráficos y revisar el historial.</p>
    <p>Las imágenes son capturas reales del módulo en funcionamiento con datos ficticios. El personal realiza los ajustes del equipo conforme a sus procedimientos y registra una nueva medición para seguir sus resultados.</p>
    <div class="access"><strong>Consulta del historial para supervisores</strong><p>Abra este enlace para consultar el historial, ver los gráficos y guardar los registros seleccionados en PDF. No necesita contraseña.</p><a href="{{ $consultaUrl }}">{{ $consultaUrl }}</a></div>
    <p class="note">El enlace se comparte con las personas encargadas de la revisión. Ofrece consulta y exportación de datos; los registros se mantienen sin modificaciones.</p>
</section>
@foreach($paginas as [$imagen, $titulo, $texto])
    @php([$ancho, $alto] = getimagesize($imagen))
    @php($escala = min(1040 / $ancho, 475 / $alto))
    <section class="page"><h2>{{ $titulo }}</h2><p>{{ $texto }}</p><img style="width: {{ $ancho * $escala }}px; height: {{ $alto * $escala }}px; border: 1px solid #dbe3ea" src="{{ $imagen }}" alt="{{ $titulo }}"></section>
@endforeach
</body></html>
