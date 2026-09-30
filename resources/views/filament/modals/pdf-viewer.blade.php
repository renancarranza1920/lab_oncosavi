@php
    $src = $pdfUrl ?? ('data:application/pdf;base64,' . ($pdfContent ?? ''));
@endphp

<div style="height: 80vh; min-height: 500px;">
    <iframe
        src="{{ $src }}"
        style="width: 100%; height: 100%; border: none;"
        title="Visor de Reporte PDF"
    >
        <p class="text-center text-gray-500 p-8">
            Tu navegador no soporta iframes.
            <a href="{{ $src }}" target="_blank" class="text-primary-600 font-medium">Puedes abrir el PDF aqui.</a>
        </p>
    </iframe>
</div>
