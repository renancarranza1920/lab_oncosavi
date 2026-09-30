<div>
 <div>
    <h3>Previsualización de etiquetas</h3>
    <ul>
        @foreach ($examenes as $examen)
            <li>{{ $examen['nombre'] ?? 'Sin nombre' }}</li>
        @endforeach
    </ul>
</div>

    <x-filament::card class="mb-4">
        <h2 class="text-xl font-bold">Orden #{{ $ordenId }}</h2>
        <p>Fecha: {{ $ordenFecha }}</p>
        <p>Paciente: {{ $clienteNombre }} (Expediente: {{ $clienteExpediente }})</p>
        <x-filament::button wire:click="imprimirEtiquetas" class="mt-4">
            Imprimir Etiquetas
        </x-filament::button>

       @if (session()->has('message'))
    <div class="p-4 mb-4 text-sm text-green-700 bg-green-100 rounded-lg" role="alert">
        {{ session('message') }}
    </div>
@endif

@if (session()->has('error'))
    <div class="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg" role="alert">
        {{ session('error') }}
    </div>
@endif

    </x-filament::card>

    <div class="mb-4 flex gap-2 items-center">
        <x-filament::input type="text" wire:model.live="newColumnName" placeholder="Nombre de nueva columna/recipiente" class="flex-grow" />
        <x-filament::button wire:click="addColumn">
            Añadir Columna
        </x-filament::button>
    </div>

    {{-- Contenedor del Kanban --}}
    <div class="kanban-board grid grid-cols-1 md:grid-cols-{{ count($kanbanColumns) > 0 ? (count($kanbanColumns) > 4 ? 4 : count($kanbanColumns)) : 1 }} gap-4"
        x-data="{}"
    >
        @foreach($kanbanColumns as $columnId => $column)
            <div class="kanban-column bg-gray-100 p-4 rounded-lg shadow"
                 x-data="{}"
            >
                <h3 class="font-bold text-lg mb-4 flex justify-between items-center">
                    {{ $column['name'] }} ({{ count($column['items']) }})
                    <x-filament::button wire:click="removeColumn('{{ $columnId }}')"
                                        color="danger"
                                        size="sm"
                                        icon="heroicon-o-trash"
                                        :disabled="!empty($column['items'])"
                                        tooltip="Eliminar columna vacía"
                                        class="ml-2"
                    />
                </h3>
                <div class="kanban-items space-y-2 min-h-[50px] border border-dashed border-gray-300 p-2 rounded"
                     id="column-{{ $columnId }}"
                     x-init="
                        new Sortable($el, {
                            group: 'kanban',
                            animation: 150,
                            onEnd: (evt) => {
                                @this.call('updateKanbanOrder', {
                                    item_id: evt.item.dataset.itemId, // Ahora es el temp_id
                                    from_column_id: evt.from.id.replace('column-', ''),
                                    to_column_id: evt.to.id.replace('column-', ''),
                                    new_index: evt.newIndex
                                });
                            }
                        });
                    "
                >
                    @foreach($column['items'] as $item)
                        <div class="kanban-item bg-white p-3 rounded shadow-sm cursor-grab"
                             data-item-id="{{ $item['temp_id'] }}" {{-- Usamos el temp_id --}}
                        >
                            <p class="font-semibold">{{ $item['nombre_examen'] }}</p>
                            <p class="text-sm text-gray-600">
                                @if ($item['origen_tipo'] === 'perfil')
                                    (De Perfil: {{ $item['perfil_nombre'] }})
                                @else
                                    (Individual)
                                @endif
                            </p>
                            <p class="text-xs text-gray-500">Recipiente por defecto: {{ $item['recipiente_default'] }}</p>
                            <p class="text-xs text-gray-500">Recipiente actual: {{ $item['recipiente_actual'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>

{{-- Incluye SortableJS --}}
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
@endpush