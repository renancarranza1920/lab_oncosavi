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
                            <a href="{{ route('filament.admin.resources.examens.view', $examen['id']) }}"
   target="_blank" rel="noopener"
   class="ui-chip ui-chip-link">
    {{ $examen['nombre'] }}
</a>


                    @endforeach
                </div>
            </div>
        @empty
            <p class="text-sm text-gray-500 dark:text-gray-300">Este perfil no tiene exámenes asignados.</p>
        @endforelse
    </div>
</div>
