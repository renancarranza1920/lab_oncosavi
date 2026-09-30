<div>
    <h2 class="text-lg font-semibold mb-2">Muestras Requeridas para la Orden</h2>
    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
        A continuación se muestra el consolidado de todos los recipientes necesarios para completar esta orden.
    </p>

    @if ($muestrasConsolidadas->isEmpty())
        <div class="p-4 text-center bg-yellow-50 dark:bg-yellow-900/10 rounded-lg">
            <p class="text-yellow-700 dark:text-yellow-400">No se han definido muestras para los exámenes de esta orden.</p>
        </div>
    @else
        <div class="border rounded-lg overflow-hidden dark:border-gray-700">
            <ul class="divide-y dark:divide-gray-700">
                @foreach ($muestrasConsolidadas as $nombreMuestra => $cantidad)
                    <li class="p-3 flex items-center space-x-3">
                        <span class="font-mono text-lg text-primary-600 dark:text-primary-500 font-bold">{{ $cantidad }}x</span>
                        <span class="text-gray-800 dark:text-gray-200">{{ $nombreMuestra }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mt-6 p-3 text-center bg-gray-50 dark:bg-gray-900/50 rounded-lg">
        <p class="text-xs text-gray-500 dark:text-gray-400">
            Al confirmar, la orden pasará al estado "En Proceso" y se registrará la fecha y hora actual como momento de la recolección.
        </p>
    </div>
</div>
