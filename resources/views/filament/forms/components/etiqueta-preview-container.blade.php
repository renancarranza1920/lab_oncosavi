@php
    use App\Models\Perfil;
    use App\Models\Examen;

    $clienteId = $record->cliente_id ?? null;

    $perfiles = Perfil::whereIn('id', [1, 2])->get()->toArray();
    $examenes = Examen::whereIn('id', [1, 2])->get()->toArray();
@endphp

<livewire:etiqueta-editor-livewire
    :cliente-id="$clienteId"
    :perfiles="$perfiles"
    :examenes="$examenes"
/>
