@php
    $subtotal = 0;
    $total = 0;
@endphp

<div class="flex justify-center w-full">
    
    <div class="w-full max-w-2xl bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">

        <div class="bg-gray-50 dark:bg-gray-600 p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex justify-between items-start">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white uppercase tracking-wide">
                        Resumen de Orden
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        {{ now()->translatedFormat('d \d\e F \d\e Y') }}
                    </p>
                </div>
                <div class="text-right">
                    @if ($cliente)
                        <div class="text-right">
                            <h3 class="text-lg font-bold text-danger-600 dark:text-danger-400">
                                {{ $cliente->nombre }} {{ $cliente->apellido }}
                            </h3>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300 mt-1">
                                Exp: {{ $cliente->NumeroExp }}
                            </span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300 mt-1">
                                DUI: {{ $cliente->dui ?? 'N/A' }}
                            </span>
                        </div>
                    @else
                        <span class="text-warning-600 text-sm font-medium">Sin Cliente</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="p-6 space-y-6">
            
            {{-- LISTA DE ÍTEMS --}}
            <div class="space-y-4">
                
                {{-- PERFILES --}}
                @if (!empty($perfilesSeleccionados))
                    @foreach ($perfilesSeleccionados as $item)
                        @php
                            $perfil = \App\Models\Perfil::with(['examenes' => function ($query) {
                                $query->where('estado', 1);
                            }])->find($item['perfil_id']);

                            if (!$perfil) continue;
                            $precioPerfil = floatval($item['precio_hidden'] ?? $perfil->precio);
                            $subtotal += $precioPerfil;
                        @endphp

                        <div class="group">
                            {{-- Encabezado del Perfil --}}
                            <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700/50">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-primary-50 dark:bg-primary-900/20 rounded-lg text-primary-600 dark:text-primary-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-lg font-bold text-gray-900 dark:text-white leading-tight">
                                            {{ $perfil->nombre }}
                                        </h4>
                                        <span class="text-xs font-semibold text-primary-600 dark:text-primary-400 uppercase tracking-wider">
                                            Perfil Completo
                                        </span>
                                    </div>
                                </div>
                                <div class="text-lg font-bold text-gray-900 dark:text-white font-mono">
                                    ${{ number_format($precioPerfil, 2) }}
                                </div>
                            </div>

                            {{-- Lista de exámenes del perfil (Más visible) --}}
                            <div class="mt-2 ml-12 pl-4 border-l-2 border-gray-100 dark:border-gray-700 space-y-1">
                                @foreach ($perfil->examenes as $examen)
                                    <div class="text-sm text-gray-600 dark:text-gray-400 flex items-center gap-2">
                                        <span class="w-1.5 h-1.5 rounded-full bg-gray-300 dark:bg-gray-600"></span>
                                        {{ $examen->nombre }}
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @endif

                {{-- EXÁMENES SUELTOS --}}
                @if (!empty($examenesSeleccionados))
                    <div class="pt-2">
                        @foreach ($examenesSeleccionados as $item)
                            @php
                                $examen = \App\Models\Examen::find($item['examen_id']);
                                if (!$examen) continue;
                                $precioExamen = floatval($item['precio_hidden'] ?? $examen->precio);
                                $subtotal += $precioExamen;
                            @endphp

                            <div class="flex justify-between items-center py-3 border-b border-dashed border-gray-200 dark:border-gray-700 last:border-0">
                                <div class="flex items-center gap-3">
                                    <div class="p-1.5 bg-gray-100 dark:bg-gray-700 rounded-md text-gray-500 dark:text-gray-300">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                                        </svg>
                                    </div>
                                    <span class="text-base font-medium text-gray-700 dark:text-gray-200">
                                        {{ $examen->nombre }}
                                    </span>
                                </div>
                                <div class="text-base font-medium text-gray-900 dark:text-white font-mono">
                                    ${{ number_format($precioExamen, 2) }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

            </div>

            @php
                $codigoAplicado = $codigoAplicado ?? null;
                $descuento = $descuento ?? 0;
                $totalFinal = $subtotal - $descuento;
                if ($totalFinal < 0) $totalFinal = 0;
            @endphp

            @if ($subtotal > 0)
                <div class="mt-8 bg-gray-50 dark:bg-gray-600 rounded-xl p-6 border border-gray-100 dark:border-gray-700">
                    <div class="space-y-3">
                        <div class="flex justify-between text-gray-600 dark:text-gray-400">
                            <span>Subtotal</span>
                            <span class="font-mono">${{ number_format($subtotal, 2) }}</span>
                        </div>

                        @if ($codigoAplicado)
                            <div class="flex justify-between text-success-600 dark:text-success-400">
                                <span>Descuento ({{ $codigoAplicado['codigo'] }})</span>
                                <span class="font-mono font-bold">- ${{ number_format($descuento, 2) }}</span>
                            </div>
                        @endif

                        <div class="pt-4 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center">
                            <span class="text-xl font-bold text-gray-900 dark:text-white">Total a Pagar</span>
                            <span class="text-2xl font-bold text-primary-600 dark:text-primary-400 font-mono">
                                ${{ number_format($totalFinal, 2) }}
                            </span>
                        </div>
                    </div>
                </div>
            @endif
            
            
           
        </div>
        
        <div class="bg-gray-50 dark:bg-gray-900/30 p-3 text-center border-t border-gray-200 dark:border-gray-700">
            <p class="text-xs text-gray-400 dark:text-gray-500">
                Esta es una vista previa y no tiene validez  hasta confirmar la orden.
            </p>
        </div>

    </div>
</div>
