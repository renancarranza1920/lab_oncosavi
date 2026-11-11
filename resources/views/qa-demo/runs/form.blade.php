@extends('qa-demo.layout')
@section('title','Registro de Control de Calidad Interno')
@section('content')
@php
 $run=$run ?? null; $parentRun=$parentRun ?? null; $configuration=$configuration ?? null;
 $kindLabels=['routine'=>'Control habitual','additional'=>'Control adicional','repeat'=>'Repetición','correction'=>'Corrección de captura','before_event'=>'Antes de calibración / mantenimiento','after_event'=>'Después de calibración / mantenimiento'];
 $currentKind=old('kind',$run['kind'] ?? $kind ?? request('kind',$parentRun?'repeat':'routine'));
 $oldValues=[]; foreach($run['measurements'] ?? [] as $measurement) $oldValues[$measurement['catalog_id']][$measurement['level']]=$measurement['raw_value'] ?? '';
 $values=old('values',$oldValues);
 $parentId=old('parent_run_id',$run['parent_run_id'] ?? $parentRun['id'] ?? request('parent_run_id'));
 $levels=$configuration['levels'] ?? [];
 $allowedParentPairs=[]; foreach($parentRun['measurements'] ?? [] as $m) $allowedParentPairs[$m['catalog_id']][$m['level']]=true;
 $equipmentId=(int)($configuration['equipment_id'] ?? request('equipment_id'));
 $operatorId=old('operator_user_id',$run?($run['operator_user_id'] ?? ''):($currentUser?->id ?? auth()->id()));
 $operatorName=$run['operator'] ?? $currentOperator ?? auth()->user()?->name;
 $shownTime=$run['measured_at'] ?? ($currentKind==='correction' ? ($parentRun['measured_at'] ?? null) : null) ?? now('America/El_Salvador')->format('Y-m-d\TH:i:s');
@endphp
<div class="page-heading"><div><p class="eyebrow">Control de Calidad Interno</p><h1>{{ $run?'Editar registro':'Registro de Control de Calidad Interno' }}</h1><p class="muted">Registra las lecturas obtenidas en el equipo.</p></div>@if($canHistory ?? false)<a class="button secondary" href="{{ route('qa-demo.runs.index') }}">Ver historial</a>@endif</div>
@if(!$run && !$parentRun)
<section class="section hide-print"><form method="get" action="{{ route('qa-demo.runs.create') }}" class="filters" data-area-selector>
<div><label for="choose-equipment">Área y equipo</label><select id="choose-equipment" name="equipment_id" data-area-equipment><option value="">Seleccionar área y equipo</option>@foreach($equipment ?? [] as $eq)@if($eq['active'] ?? true)<option value="{{ $eq['id'] }}" @selected($equipmentId===(int)$eq['id'])>{{ $eq['area'] ?? 'Química' }} · {{ $eq['name'] }}</option>@endif @endforeach</select></div>
<div><label for="choose-configuration">Configuración de control</label><select id="choose-configuration" name="configuration_id" data-area-configuration><option value="">Configuración actual del área</option>@foreach($configurations ?? [] as $cfg)<option value="{{ $cfg['id'] }}" data-equipment="{{ $cfg['equipment_id'] }}" @selected((int)($configuration['id'] ?? 0)===(int)$cfg['id'])>{{ $cfg['equipment_name'] }} · {{ $cfg['label'] }} · v{{ $cfg['version'] }}{{ !empty($cfg['active'])?'':' · histórica' }}</option>@endforeach</select></div><div class="filter-submit"><button type="submit" class="secondary">Cargar pruebas</button></div>
@if(request('event_id'))<input type="hidden" name="event_id" value="{{ request('event_id') }}"><input type="hidden" name="kind" value="{{ $currentKind }}">@endif
</form></section>
@endif
@if($configuration)
@if($run && ($run['status'] ?? '')!=='draft')<div class="notice">La edición conservará la versión anterior en el historial de cambios del registro.</div>@endif
@if($parentRun)<div class="notice">{{ $currentKind==='correction'?'Corrección':'Repetición' }} del registro {{ $parentRun['number'] }}. Se conserva el registro original.</div>@endif
<section class="section"><div class="section-heading"><div><h2>{{ data_get($configuration,'equipment.area','Química') }} · {{ data_get($configuration,'equipment.name','Equipo') }}</h2><p class="muted">{{ $configuration['label'] }} · Versión {{ $configuration['version'] }}</p></div><span class="badge">{{ count($configuration['analytes'] ?? []) }} pruebas · {{ count($levels) }} niveles</span></div><div class="grid auto">@foreach($levels as $key=>$level)<div class="level-card"><h3>{{ $level['label'] ?? $key }}</h3><strong>{{ $level['lot'] ?? '' }}</strong><p class="hint">{{ $level['product'] ?? '' }}</p><p class="hint">Uso hasta {{ $level['use_until'] ?? '' }}</p></div>@endforeach</div></section>
<form action="{{ $run?route('qa-demo.runs.update',$run['id']):route('qa-demo.runs.store') }}" method="post" data-save-form data-run-form data-testid="run-form">@csrf @if($run) @method('PUT')<input type="hidden" name="expected_revision" value="{{ $run['revision'] ?? 1 }}">@endif
<input type="hidden" name="configuration_id" value="{{ $configuration['id'] }}">@if($parentId)<input type="hidden" name="parent_run_id" value="{{ $parentId }}">@endif
<section class="section"><div class="grid four">
<div><label for="measured-at">Fecha y hora</label><input id="measured-at" type="datetime-local" step="1" value="{{ \Carbon\Carbon::parse($shownTime)->format('Y-m-d\TH:i:s') }}" readonly @if(!$run && !$parentRun) data-auto-clock @endif><p class="hint">{{ $run?'Fecha y hora de la medición original.':'Se asignan automáticamente al guardar. Hora de El Salvador.' }}</p></div>
<div><label for="operator">Usuario laboratorista</label>@if($canAssignOperator ?? false)<select id="operator" name="operator_user_id" @required(!$run)>@if($run && !collect($operatorOptions ?? [])->contains(fn($option)=>(string)$option['id']===(string)$operatorId))<option value="{{ $operatorId }}" selected>{{ $operatorName }} · autor original</option>@endif @foreach($operatorOptions ?? [] as $option)<option value="{{ $option['id'] }}" @selected((string)$operatorId===(string)$option['id'])>{{ $option['name'] }}</option>@endforeach</select>@else<input id="operator" value="{{ $operatorName }}" readonly>@endif</div>
<div><label for="run-kind">Tipo de control</label>@if($run)<input id="run-kind" value="{{ $kindLabels[$run['kind']] ?? $run['kind'] }}" readonly><input type="hidden" name="kind" value="{{ $run['kind'] }}">@else<select id="run-kind" name="kind">@foreach($kindLabels as $key=>$label)@if($key!=='before_event' && ($parentId?in_array($key,['repeat','correction']):!in_array($key,['repeat','correction'])))<option value="{{ $key }}" @selected($currentKind===$key)>{{ $label }}</option>@endif @endforeach</select>@endif</div>
<div><label for="run-event">Evento relacionado</label>@if($run)@php $originalEvent=collect($events ?? [])->firstWhere('id',(int)($run['event_id'] ?? 0)); @endphp<input id="run-event" readonly value="{{ empty($run['event_id'])?'Sin evento':($originalEvent?(($originalEvent['type']==='calibration'?'Calibración':'Mantenimiento').' · '.\Carbon\Carbon::parse($originalEvent['occurred_at'])->format('d/m H:i')):'Evento #'.$run['event_id']) }}"><input type="hidden" name="event_id" value="{{ $run['event_id'] ?? '' }}">@else<select id="run-event" name="event_id"><option value="">Sin evento</option>@foreach($events ?? [] as $event)<option value="{{ $event['id'] }}" @selected((string)old('event_id',request('event_id'))===(string)$event['id'])>{{ $event['type']==='calibration'?'Calibración':'Mantenimiento' }} · {{ \Carbon\Carbon::parse($event['occurred_at'])->format('d/m H:i') }}</option>@endforeach</select>@endif</div>
</div></section>
<section class="section"><div class="section-heading"><div><h2>Registro de las pruebas</h2><p class="muted">Admite coma o punto decimal. Deja vacía una lectura que no se realizó e indica el motivo.</p></div></div><div class="reading-tests">
@foreach($configuration['analytes'] ?? [] as $analyte)
<article class="reading-test"><div class="reading-test-heading"><h3>{{ $analyte['name'] }}</h3><span class="badge">{{ $analyte['unit'] }}</span></div><p class="hint">{{ $analyte['method'] ?? '' }}</p><div class="reading-levels">
@foreach($levels as $levelKey=>$level)
<div class="reading-level"><label for="reading-{{ $analyte['catalog_id'] }}-{{ $levelKey }}">{{ $level['label'] ?? $levelKey }}</label>@if(!$parentRun || isset($allowedParentPairs[$analyte['catalog_id']][$levelKey]))<input id="reading-{{ $analyte['catalog_id'] }}-{{ $levelKey }}" name="values[{{ $analyte['catalog_id'] }}][{{ $levelKey }}]" inputmode="decimal" maxlength="30" value="{{ data_get($values,$analyte['catalog_id'].'.'.$levelKey,'') }}" autocomplete="off" aria-describedby="range-{{ $analyte['catalog_id'] }}-{{ $levelKey }}">@else<span class="muted small">Sin lectura original</span>@endif<p class="range" id="range-{{ $analyte['catalog_id'] }}-{{ $levelKey }}">Esperado {{ data_get($analyte,'targets.'.$levelKey.'.target') }}<br>Límites {{ data_get($analyte,'targets.'.$levelKey.'.min') }} a {{ data_get($analyte,'targets.'.$levelKey.'.max') }}</p></div>
@endforeach
</div></article>
@endforeach
</div></section>
<section class="section"><div class="grid two"><div><label for="missing-reason">Motivo de las lecturas no realizadas</label><textarea id="missing-reason" name="missing_reason" rows="3" maxlength="2000">{{ old('missing_reason',$run['missing_reason'] ?? '') }}</textarea></div><div><label for="run-notes">Observaciones <span class="muted small">(opcional)</span></label><textarea id="run-notes" name="notes" rows="3" maxlength="4000" @required($currentKind==='correction')>{{ old('notes',$run['notes'] ?? '') }}</textarea></div></div>
@if($run)<div class="section-actions"><label for="edit-reason">Motivo de la edición <span class="required">*</span></label><textarea id="edit-reason" name="edit_reason" required maxlength="2000" rows="2">{{ old('edit_reason') }}</textarea></div>@endif
</section>
<div class="actions end">@if(($canEdit ?? false) && (!$run || ($run['status'] ?? '')==='draft'))<button class="secondary" type="submit" name="action" value="draft">Guardar borrador</button>@endif<button type="submit" name="action" value="submit">{{ $run?'Guardar cambios':'Guardar registro' }}</button></div>
</form>
@else<div class="empty"><h2>No hay controles configurados para esta área</h2><p>Selecciona otra área o prepara sus pruebas y niveles.</p>@if($canMaintain ?? false)<a class="button" href="{{ route('qa-demo.configuration.index') }}">Ir a mantenimiento</a>@endif</div>@endif
@endsection
