<div class="p-6" x-data="kanbanApp()" x-init="init()">
    <div class="text-xl font-bold mb-4">Organizar exámenes por recipiente - Orden #{{ $orden->id }}</div>

    <div class="flex gap-4 overflow-x-auto">
        <template x-for="(items, recipiente) in recipientes" :key="recipiente">
            <div class="bg-gray-100 p-4 rounded w-64 flex-shrink-0">
                <h3 class="font-semibold mb-2" x-text="recipiente"></h3>
                <div class="space-y-2" 
                     @drop.prevent="onDrop($event, recipiente)" 
                     @dragover.prevent>
                    <template x-for="item in items" :key="item.id">
                        <div class="bg-white p-2 rounded shadow cursor-move"
                             draggable="true"
                             @dragstart="onDragStart($event, item)">
                            <span x-text="item.nombre_examen"></span>
                            <br>
                            <small>$<span x-text="item.precio_examen"></span></small>
                        </div>
                    </template>
                </div>
            </div>
        </template>

        <div class="w-64 flex-shrink-0 bg-white p-4 border rounded">
            <h3 class="font-semibold">Nuevo recipiente</h3>
            <input x-model="nuevo" class="w-full mt-2 p-1 border rounded text-sm" placeholder="Nombre...">
            <button @click="crearRecipiente()" class="mt-2 w-full bg-blue-600 text-white text-sm px-3 py-1 rounded">Agregar</button>
        </div>
    </div>
</div>

<script>
function kanbanApp() {
    return {
        recipientes: @entangle('recipientes').defer,
        nuevo: '',
        dragging: null,

        init() {
            console.log('Kanban listo');
        },

        onDragStart(e, item) {
            this.dragging = item;
        },

        onDrop(e, recipienteDestino) {
            if (!this.dragging) return;
            $wire.mover(this.dragging.id, recipienteDestino);
            this.dragging = null;
        },

        crearRecipiente() {
            if (this.nuevo.trim() !== '') {
                this.recipientes[this.nuevo] = [];
                this.nuevo = '';
            }
        }
    }
}
</script>
