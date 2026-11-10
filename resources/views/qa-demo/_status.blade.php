@php
    $statusValue = $value ?? 'pending';
    [$statusLabel, $statusClass] = match($statusValue) {
        'within' => ['Dentro de límites', 'good'],
        'outside' => ['Fuera de límites', 'bad'],
        'incomplete', 'missing' => ['No realizado / incompleto', 'warn'],
        'draft' => ['Borrador', 'neutral'],
        'submitted' => ['Guardado', 'neutral'],
        'reviewed' => ['Revisado', 'good'],
        'followup' => ['En seguimiento', 'warn'],
        default => ['Pendiente de revisión', 'warn'],
    };
@endphp
<span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
