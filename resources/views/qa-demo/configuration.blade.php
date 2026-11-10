@extends('qa-demo.layout')
@section('title','Configurar controles')
@section('content')
@php
    $selectedConfiguration = $selectedConfiguration ?? null;
    $initialAnalytes = old('analytes', $selectedConfiguration['analytes'] ?? []);
    $selectedById = collect($initialAnalytes)->keyBy('catalog_id')->all();
    $levels = ['low'=>'Bajo','medium'=>'Medio','high'=>'Alto'];
    $formLevels = old('levels', $selectedConfiguration['levels'] ?? []);
@endphp
<div class="page-heading"><div><p class="eyebrow">Preparación de los controles</p><h1>Configuración</h1><p class="muted">Selecciona las pruebas existentes y copia los valores de la hoja del control utilizado.</p></div><div class="actions"><a class="button secondary" href="{{ route('qa-demo.runs.create') }}">Ir a registrar mediciones</a></div></div>
<div class="notice"><strong>Cada guardado crea una nueva versión.</strong> Las mediciones anteriores conservan sus lotes, fechas y límites originales. Los objetivos de calidad se guardan aparte de las referencias de pacientes.</div>
<section class="section"><div class="section-heading"><div><h2>1. Identificar los equipos</h2><p class="muted">Un equipo identifica la máquina en la que se obtienen las lecturas.</p></div></div>
<details @if(!count($equipment ?? [])) open @endif><summary>Agregar un equipo</summary><div class="details-body">
<form action="{{ route('qa-demo.equipment.store') }}" method="post" data-save-form data-testid="equipment-form">@csrf
<div class="grid four">
    <div><label for="eq-name">Nombre del equipo <span class="required">*</span></label><input id="eq-name" name="name" maxlength="120" required value="{{ old('name') }}" placeholder="Química 01"></div>
    <div><label for="eq-code">Código interno <span class="required">*</span></label><input id="eq-code" name="code" maxlength="40" pattern="[A-Za-z0-9_-]+" required value="{{ old('code') }}" placeholder="Q-01"></div>
    <div><label for="eq-model">Marca y modelo <span class="required">*</span></label><input id="eq-model" name="model" maxlength="160" required value="{{ old('model') }}" placeholder="Modelo que utiliza el laboratorio"></div>
    <div><label for="eq-notes">Observación</label><input id="eq-notes" name="notes" maxlength="1000" value="{{ old('equipment_notes') }}"></div>
</div><div class="actions end" style="margin-top:16px"><button type="submit">Guardar equipo</button></div>
</form></div></details>
@foreach($equipment ?? [] as $eq)
<details><summary>{{ $eq['name'] }} <span class="muted small">· {{ $eq['model'] ?? '' }}</span></summary><div class="details-body">
<form action="{{ route('qa-demo.equipment.update',$eq['id']) }}" method="post" data-save-form>@csrf @method('PUT')
<div class="grid four"><div><label for="eq-{{ $eq['id'] }}-name">Nombre</label><input id="eq-{{ $eq['id'] }}-name" name="name" value="{{ $eq['name'] }}" maxlength="120" required></div><div><label for="eq-{{ $eq['id'] }}-code">Código</label><input id="eq-{{ $eq['id'] }}-code" name="code" value="{{ $eq['code'] ?? '' }}" maxlength="40" pattern="[A-Za-z0-9_-]+" required></div><div><label for="eq-{{ $eq['id'] }}-model">Marca y modelo</label><input id="eq-{{ $eq['id'] }}-model" name="model" value="{{ $eq['model'] ?? '' }}" maxlength="160" required></div><div><label for="eq-{{ $eq['id'] }}-notes">Observación</label><input id="eq-{{ $eq['id'] }}-notes" name="notes" value="{{ $eq['notes'] ?? '' }}" maxlength="1000"></div></div><div class="actions end" style="margin-top:16px"><button type="submit" class="secondary">Actualizar identificación</button></div></form>
</div></details>
@endforeach
</section>
@if(count($equipment ?? []))
<section class="section"><div class="section-heading"><div><h2>2. Preparar una configuración</h2><p class="muted">Puedes comenzar desde una versión anterior para cambiar un lote, abrir nuevos frascos o corregir sus límites.</p></div></div>
<form method="get" action="{{ route('qa-demo.configuration.index') }}" class="inline-fields hide-print">
<div><label for="base-config">Partir de una configuración existente</label><select id="base-config" name="configuration_id"><option value="">Nueva configuración vacía</option>@foreach($configurations ?? [] as $cfg)<option value="{{ $cfg['id'] }}" @selected((int)($selectedConfiguration['id'] ?? 0)===(int)$cfg['id'])>{{ $cfg['equipment_name'] ?? 'Equipo' }} · {{ $cfg['label'] ?? 'Controles' }} · v{{ $cfg['version'] ?? 1 }}{{ !empty($cfg['active'])?' · actual':' · histórica' }}</option>@endforeach</select></div><div class="actions"><button type="submit" class="secondary">Cargar configuración</button></div></form>
</section>
<form action="{{ route('qa-demo.configuration.store') }}" method="post" data-save-form data-testid="configuration-form">@csrf
<section class="section"><div class="grid three">
    <div><label for="configuration-equipment">Equipo <span class="required">*</span></label><select id="configuration-equipment" name="equipment_id" required><option value="">Seleccionar equipo</option>@foreach($equipment as $eq)<option value="{{ $eq['id'] }}" @selected((string)old('equipment_id',$selectedConfiguration['equipment_id'] ?? request('equipment_id'))===(string)$eq['id'])>{{ $eq['name'] }} · {{ $eq['model'] ?? '' }}</option>@endforeach</select></div>
    <div><label for="configuration-label">Nombre de esta configuración <span class="required">*</span></label><input id="configuration-label" name="label" required maxlength="150" value="{{ old('label',$selectedConfiguration['label'] ?? '') }}" placeholder="Controles de química · noviembre"></div>
    <div><label for="configured-by">Configurado por <span class="required">*</span></label><input id="configured-by" name="configured_by" required maxlength="120" value="{{ old('configured_by',$selectedConfiguration['configured_by'] ?? '') }}" placeholder="Nombre y apellido"></div>
</div><p class="hint">Copia la unidad y el método indicados en la hoja del control utilizado.</p></section>
<section class="section"><h2>3. Identificar los controles y sus frascos</h2><p class="muted">El lote es el número que identifica el material utilizado. La apertura corresponde al frasco concreto que está en uso.</p>
<div class="grid three">
@foreach($levels as $key=>$label)
<fieldset class="level-card"><legend>Nivel {{ mb_strtolower($label) }}</legend>
<div class="stack">
<div><label for="{{ $key }}-product">Producto de control <span class="required">*</span></label><input id="{{ $key }}-product" name="levels[{{ $key }}][product]" maxlength="160" required value="{{ data_get($formLevels,$key.'.product','') }}" placeholder="Nombre del control"></div>
<div><label for="{{ $key }}-lot">Número de lote <span class="required">*</span></label><input id="{{ $key }}-lot" name="levels[{{ $key }}][lot]" maxlength="100" required value="{{ data_get($formLevels,$key.'.lot','') }}"></div>
<div><label for="{{ $key }}-expires">Caducidad del lote <span class="required">*</span></label><input id="{{ $key }}-expires" type="date" name="levels[{{ $key }}][expires_on]" required value="{{ substr((string)data_get($formLevels,$key.'.expires_on',''),0,10) }}"></div>
<div><label for="{{ $key }}-opened">Apertura del frasco <span class="required">*</span></label><input id="{{ $key }}-opened" type="date" name="levels[{{ $key }}][opened_on]" required value="{{ substr((string)data_get($formLevels,$key.'.opened_on',''),0,10) }}"></div>
<div><label for="{{ $key }}-stability">Estabilidad abierto, en días <span class="required">*</span></label><input id="{{ $key }}-stability" type="number" min="1" max="730" name="levels[{{ $key }}][stability_days]" required value="{{ data_get($formLevels,$key.'.stability_days','') }}"><p class="hint">Se copia del inserto. Se utiliza la fecha de uso más próxima.</p></div>
@if(data_get($formLevels,$key.'.use_until'))<div class="badge">Versión cargada: uso hasta {{ \Carbon\Carbon::parse(data_get($formLevels,$key.'.use_until'))->format('d/m/Y') }}</div>@endif
</div></fieldset>
@endforeach
</div></section>
<section class="section"><div class="section-heading"><div><h2>4. Elegir pruebas y escribir sus límites</h2><p class="muted">Marca las pruebas que controla el equipo. Para cada una, carga el método, la unidad y los tres niveles.</p></div><span class="badge"><span data-selected-count>{{ count($selectedById) }}</span> seleccionadas</span></div>
<div style="margin-bottom:18px"><label for="catalog-search">Buscar por prueba, examen o área</label><input id="catalog-search" type="search" data-catalog-search placeholder="Buscar glucosa, creatinina, GGT…" autocomplete="off"></div>
@forelse($catalog ?? [] as $i=>$item)
@php $selected=$selectedById[$item['id']] ?? null; $on=$selected!==null; @endphp
<details class="analyte" data-analyte data-search="{{ $item['name'] }} {{ $item['exam_name'] ?? '' }} {{ $item['area'] ?? '' }}" @if($on) open @endif>
<summary class="analyte-summary"><input type="checkbox" data-analyte-select id="catalog-{{ $item['id'] }}" @checked($on) aria-label="Seleccionar {{ $item['name'] }} del examen {{ $item['exam_name'] ?? '' }}"><label for="catalog-{{ $item['id'] }}">{{ $item['name'] }}<div class="analyte-meta">{{ $item['exam_name'] ?? '' }} · {{ $item['area'] ?? '' }}{{ !empty($item['unit'])?' · '.$item['unit']:'' }}</div></label></summary>
<div class="details-body">
<input data-analyte-field type="hidden" name="analytes[{{ $i }}][catalog_id]" value="{{ $item['id'] }}" @disabled(!$on)>
<div class="analyte-fields"><div><label for="method-{{ $item['id'] }}">Método de esta prueba en el equipo <span class="required">*</span></label><input data-analyte-field id="method-{{ $item['id'] }}" name="analytes[{{ $i }}][method]" maxlength="120" required value="{{ $selected['method'] ?? '' }}" @disabled(!$on)></div><div><label for="unit-{{ $item['id'] }}">Unidad <span class="required">*</span></label><input data-analyte-field id="unit-{{ $item['id'] }}" name="analytes[{{ $i }}][unit]" maxlength="40" required value="{{ $selected['unit'] ?? $item['unit'] ?? '' }}" @disabled(!$on)></div></div>
<div class="table-wrap"><table><thead><tr><th>Nivel</th><th>Valor esperado</th><th>Mínimo</th><th>Máximo</th></tr></thead><tbody>
@foreach($levels as $levelKey=>$levelLabel)<tr><th scope="row">{{ $levelLabel }}</th>@foreach(['target'=>'esperado','min'=>'mínimo','max'=>'máximo'] as $fieldKey=>$fieldLabel)<td><label class="sr-only" for="target-{{ $item['id'] }}-{{ $levelKey }}-{{ $fieldKey }}">{{ $item['name'] }} {{ $levelLabel }} {{ $fieldLabel }}</label><input data-analyte-field id="target-{{ $item['id'] }}-{{ $levelKey }}-{{ $fieldKey }}" name="analytes[{{ $i }}][targets][{{ $levelKey }}][{{ $fieldKey }}]" inputmode="decimal" maxlength="30" required value="{{ data_get($selected,'targets.'.$levelKey.'.'.$fieldKey,'') }}" @disabled(!$on)></td>@endforeach</tr>@endforeach
</tbody></table></div>
</div></details>
@empty<div class="empty"><h3>No hay un catálogo preparado</h3><p>El catálogo de pruebas debe estar disponible para configurar los controles.</p></div>@endforelse
<p class="hint">Admite coma o punto decimal. El valor esperado debe quedar entre el mínimo y el máximo.</p>
</section>
<div class="actions end"><a class="button secondary" href="{{ route('qa-demo.index') }}">Volver al resumen</a><button type="submit">Guardar nueva versión</button></div>
</form>
@else<div class="notice warning">Agrega el primer equipo para poder seleccionar sus pruebas y configurar los controles.</div>@endif
@endsection
