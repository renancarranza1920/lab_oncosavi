@php $runKindLabels=['routine'=>'Control habitual','additional'=>'Control adicional','repeat'=>'Repetición','correction'=>'Corrección','before_event'=>'Antes de evento','after_event'=>'Después de evento']; @endphp
@if(count($runs ?? []))
<div class="table-wrap table-mobile-cards"><table><thead><tr><th>Fecha de medición</th><th>Registro / área</th><th>Laboratorista</th><th>Lectura</th><th>Revisión</th><th>Acciones</th></tr></thead><tbody>
@foreach($runs as $item)
<tr>
<td data-label="Fecha de medición" class="nowrap">{{ \Carbon\Carbon::parse($item['measured_at'])->format('d/m/Y H:i') }}</td>
<td data-label="Registro / área"><strong>{{ $item['number'] ?? 'QC-'.$item['id'] }}</strong><div class="hint">{{ data_get($item,'equipment.area',$item['area'] ?? '') }} · {{ data_get($item,'equipment.name',$item['equipment_name'] ?? '') }}</div><div class="hint">{{ $runKindLabels[$item['kind'] ?? 'routine'] ?? 'Control' }}</div></td>
<td data-label="Laboratorista">{{ $item['operator'] ?? '' }}</td>
<td data-label="Lectura">@if(!empty($item['deleted_at']))<span class="badge bad">Eliminado</span>@else @include('qa-demo._status',['value'=>($item['status'] ?? '')==='draft'?'draft':($item['comparison'] ?? 'incomplete')])@endif</td>
<td data-label="Revisión">@if(($item['status'] ?? '')==='draft')<span class="muted small">Sin presentar</span>@else @include('qa-demo._status',['value'=>$item['review_status'] ?? 'pending']) @endif</td>
<td><div class="actions">@if($canViewRecord ?? $canHistory ?? false)<a class="text-link" href="{{ route('qa-demo.runs.show',$item['id']) }}">Ver detalle</a>@endif @if(($canEdit ?? false) && empty($item['deleted_at']))<a class="text-link" href="{{ route('qa-demo.runs.edit',$item['id']) }}">Editar</a>@endif</div></td>
</tr>
@endforeach
</tbody></table></div>
@else<div class="empty"><h3>No hay registros con estos filtros</h3><p>Los controles registrados aparecerán aquí.</p>@if($canRegister ?? false)<a class="button" href="{{ route('qa-demo.runs.create') }}">Registrar control</a>@endif</div>@endif
