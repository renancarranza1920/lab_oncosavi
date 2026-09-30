@php
    $total = 0;
@endphp
<div>
    <div class="p-6 bg-white dark:bg-gray-800 rounded-lg border dark:border-gray-700">
        <div class="flex justify-between items-center border-b pb-4 mb-6 dark:border-gray-700">
            <div class="text-left">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Resumen de Cotización</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Fecha de Emisión: {{ now()->translatedFormat('d \d\e F \d\e Y') }}</p>
            </div>
        </div>
        @if ($nombre_cliente)
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-400 mb-2">Cliente</h3>
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    <p><strong>Nombre:</strong> {{ $nombre_cliente }}</p>
                </div>
            </div>
        @endif

        {{-- Aquí reutilizamos la tabla de tu comprobante --}}
        <table class="w-full text-left">
             <thead><tr><th class="pb-2 border-b text-sm font-semibold text-gray-600 dark:text-gray-400">Descripción</th><th class="pb-2 border-b text-sm font-semibold text-gray-600 dark:text-gray-400 text-right">Precio</th></tr></thead>
            <tbody class="divide-y dark:divide-gray-700">
                @if (!empty($perfilesSeleccionados))
                    @foreach ($perfilesSeleccionados as $item)
                        @php
                            // --- CAMBIO AQUÍ: Filtramos solo exámenes activos ---
                            $perfil = \App\Models\Perfil::with(['examenes' => function ($query) {
                                $query->where('estado', 1);
                            }])->find($item['perfil_id']);
                            
                            if (!$perfil) continue;
                            $precioPerfil = floatval($item['precio_hidden'] ?? $perfil->precio);
                            $total += $precioPerfil;
                        @endphp
                        <tr class="font-bold"><td class="py-3 pr-4">{{ $perfil->nombre }}</td><td class="py-3 text-right font-mono">${{ number_format($precioPerfil, 2) }}</td></tr>
                        @if ($perfil->examenes->isNotEmpty())
                            @foreach ($perfil->examenes as $examen)
                                <tr class="text-sm text-gray-600 dark:text-gray-400"><td class="pt-0 pb-2 pl-6 pr-4">- {{ $examen->nombre }}</td><td></td></tr>
                            @endforeach
                        @endif
                    @endforeach
                @endif
                @if (!empty($examenesSeleccionados))
                    @foreach ($examenesSeleccionados as $item)
                        @php
                            // Opcional: También podrías filtrar aquí si quisieras ocultar exámenes sueltos inactivos
                            $examen = \App\Models\Examen::find($item['examen_id']);
                            if (!$examen) continue;
                            $precioExamen = floatval($item['precio_hidden'] ?? $examen->precio);
                            $total += $precioExamen;
                        @endphp
                        <tr><td class="py-3 pr-4">{{ $examen->nombre }}</td><td class="py-3 text-right font-mono">${{ number_format($precioExamen, 2) }}</td></tr>
                    @endforeach
                @endif
            </tbody>
        </table>
        @if($total > 0)
        <div class="mt-6 pt-4 border-t-2 border-gray-300 dark:border-gray-600">
            <div class="flex justify-between items-center text-xl font-bold">
                <span class="text-gray-900 dark:text-white">Total a Pagar:</span>
                <span class="font-mono text-success-600 dark:text-success-500">${{ number_format($total, 2) }}</span>
            </div>
        </div>
        @endif
    </div>
</div>