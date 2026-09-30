<x-filament-panels::page>
    {{-- 1. Sección: Datos del Paciente (Renderiza el Infolist) --}}
    <div>
        {{ $this->clienteInfolist }}
    </div>

    {{-- 2. Separador y Título para la Tabla --}}
    <div class="mt-8 mb-4">
        <h2 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white flex items-center gap-2">
            <x-heroicon-o-clipboard-document-list class="w-6 h-6 text-primary-600"/>
            Historial de Órdenes
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Listado de todas las órdenes registradas para este paciente.
        </p>
    </div>

    {{-- 3. Sección: Tabla de Órdenes (Renderiza la Tabla) --}}
    <div>
        {{ $this->table }} 
    </div>
</x-filament-panels::page>