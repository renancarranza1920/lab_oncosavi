@php
    $runKindLabels = ['routine'=>'Control habitual','additional'=>'Control adicional','repeat'=>'Repetición','correction'=>'Corrección','before_event'=>'Antes de evento','after_event'=>'Después de evento'];
@endphp
@if(count($runs ?? []))
<div class="table-wrap table-mobile-cards">
<table><thead><tr><th>Fecha de medición</th><th>Registro / equipo</th><th>Registrado por</th><th>Lectura</th><th>Revisión</th><th>Acciones</th></tr></thead><tbody>
@foreach($runs as $item)
<tr>
    <td data-label="Fecha de medición" class="nowrap">{{ \Carbon\Carbon::parse($item['measured_at'])->format('d/m/Y H:i') }}</td>
    <td data-label="Registro / equipo"><strong>{{ $item['number'] ?? ('QC-'.$item['id']) }}</strong><div class="hint">{{ data_get($item,'equipment.name',$item['equipment_name'] ?? '') }} · {{ $runKindLabels[$item['kind'] ?? 'routine'] ?? 'Control' }}</div></td>
    <td data-label="Registrado por">{{ $item['operator'] ?? '' }}</td>
    <td data-label="Lectura">@include('qa-demo._status',['value'=>($item['status'] ?? '')==='draft'?'draft':($item['comparison'] ?? 'incomplete')])</td>
    <td data-label="Revisión">@if(($item['status'] ?? '')==='draft')<span class="muted small">Aún sin presentar</span>@else @include('qa-demo._status',['value'=>$item['review_status'] ?? 'pending']) @endif</td>
    <td><div class="actions"><a class="text-link" href="{{ route('qa-demo.runs.show',$item['id']) }}">Ver detalle</a>@if(($item['status'] ?? '')==='draft')<a class="text-link" href="{{ route('qa-demo.runs.edit',$item['id']) }}">Editar borrador</a>@endif</div></td>
</tr>
@endforeach
</tbody></table>
</div>
@else
<div class="empty"><h3>No hay mediciones con estos filtros</h3><p>Registra las lecturas de un control para comenzar el seguimiento.</p><a class="button" href="{{ route('qa-demo.runs.create') }}">Registrar mediciones</a></div>
@endif
