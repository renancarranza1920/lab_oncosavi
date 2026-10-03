<x-filament-panels::page>
    @if (auth()->user()->can('manage_settings'))
        <x-filament::section heading="WhatsApp del laboratorio">
            <div @if (in_array($estadoConexion, ['conectando', 'pendiente de escanear el QR', 'conectado', 'desconectado'])) wire:poll.8s="consultarConexion" @endif>
                <p class="text-sm text-gray-600 dark:text-gray-300">Estado: {{ $estadoConexion }}.</p>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Pulse Vincular WhatsApp. En el teléfono del laboratorio, abra WhatsApp → Dispositivos vinculados → Vincular un dispositivo y escanee el QR.</p>
                @if ($qr)
                    <img src="{{ $qr }}" alt="QR para vincular el WhatsApp del laboratorio" class="mt-4 h-64 w-64 rounded-lg bg-white" />
                @endif
            </div>
        </x-filament::section>
    @endif
    <p class="text-sm text-gray-600 dark:text-gray-300">“Enviado” confirma el envío por el teléfono vinculado; no confirma entrega ni lectura. Si aparece “desconocido”, revise el chat antes de reenviar. Los envíos no se repiten automáticamente.</p>
    {{ $this->table }}
</x-filament-panels::page>
