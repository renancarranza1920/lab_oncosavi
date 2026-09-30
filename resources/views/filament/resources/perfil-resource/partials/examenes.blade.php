<div class="rounded-2xl border shadow-sm p-4 bg-white dark:bg-gray-800 transition">
    <div class="flex justify-between items-center mb-2">
        <h3 class="text-lg font-semibold">Exámenes Seleccionados</h3>
        {{-- Puedes agregar un botón para colapsar si es necesario --}}
    </div>

   
    @php
        $examenes = $getState();
        $agrupadosSeleccionados = [];

        foreach ($examenes as $examen) {
            $agrupadosSeleccionados[$examen['tipo']][] = $examen;
        }
    @endphp

    <div class="space-y-4 transition-all duration-500 overflow-hidden">
        @forelse($agrupadosSeleccionados as $tipo => $examenesPorTipo)
            <div class="p-3 rounded-xl shadow-md bg-gray-100 dark:bg-gray-700">
                <h4 class="font-bold mb-2">{{ $tipo }}</h4>
                <div class="flex flex-wrap gap-2">
                    @foreach($examenesPorTipo as $examen)
                        <div style="cursor: pointer; background-color:#090B3B; color: white; padding: 0.25rem 0.75rem; font-size: 0.875rem; border-radius: 9999px; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); transition: all 0.2s ease;"
                            onmouseover="this.style.backgroundColor='#32768C '"
                            onmouseout="this.style.backgroundColor='#090B3B'">
                            <a href="{{ route('filament.admin.resources.examens.view', $examen['id']) }}"
   target="_blank" rel="noopener"
   class="text-blue-600 hover:underline">
    {{ $examen['nombre'] }}
</a>


                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <p class="text-sm text-gray-500 dark:text-gray-300">Este perfil no tiene exámenes asignados.</p>
        @endforelse
    </div>
</div>
