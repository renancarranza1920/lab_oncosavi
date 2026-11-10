@php
    $pdfMode=$pdfMode ?? false;
    $points=array_values(array_filter($level['points'] ?? [],fn($point)=>isset($point['value'])&&is_numeric($point['value'])));
    $target=(float)($level['target'] ?? 0);$minimum=(float)($level['min'] ?? 0);$maximum=(float)($level['max'] ?? 1);
    $allValues=array_merge([$minimum,$maximum,$target],array_map(fn($point)=>(float)$point['value'],$points));
    $range=max(max($allValues)-min($allValues),abs($target)*.02,1);
    $bottom=min($allValues)-$range*.18;$top=max($allValues)+$range*.18;
    $pointY=fn($value)=>234-(((float)$value-$bottom)/($top-$bottom))*210;
    $pointX=fn($index)=>count($points)>1?52+($index/(count($points)-1))*490:297;
    $displayNumber=fn($value)=>rtrim(rtrim(number_format((float)$value,6,',',''),'0'),',');
    $polyline=implode(' ',array_map(fn($index)=>round($pointX($index),2).','.round($pointY($points[$index]['value']),2),array_keys($points)));
    $tickIndexes=count($points)?array_values(array_unique([0,(int)floor((count($points)-1)/2),count($points)-1])):[];
    $chartName=($level['label'] ?? 'Nivel').' · '.($level['unit'] ?? '');
@endphp
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 590 290" class="chart-svg" role="img" aria-label="{{ $chartName }}: {{ count($points) }} lecturas, esperado {{ $displayNumber($target) }}, límites {{ $displayNumber($minimum) }} a {{ $displayNumber($maximum) }}">
    <title>{{ $chartName }}. Mediciones frente al valor esperado y los límites configurados.</title>
    <rect x="0" y="0" width="590" height="290" fill="white"/>
    <rect x="49" y="{{ $pointY($maximum) }}" width="499" height="{{ max(0,$pointY($minimum)-$pointY($maximum)) }}" fill="#e7f4f6"/>
    @foreach(array_unique([$minimum,$target,$maximum]) as $tick)
        <line x1="49" x2="548" y1="{{ $pointY($tick) }}" y2="{{ $pointY($tick) }}" stroke="{{ (float)$tick===$target?'#26586e':'#9aabb9' }}" stroke-width="{{ (float)$tick===$target?'1.7':'1' }}" stroke-dasharray="{{ (float)$tick===$target?'6 5':'3 5' }}"/>
        <text x="42" y="{{ $pointY($tick)+4 }}" text-anchor="end" font-family="DejaVu Sans, sans-serif" font-size="12" fill="#526176">{{ $displayNumber($tick) }}</text>
    @endforeach
    <line x1="49" x2="548" y1="242" y2="242" stroke="#c7d3de"/>
    @if(count($points)>1)<polyline points="{{ $polyline }}" fill="none" stroke="#2563a6" stroke-width="2.4" stroke-linejoin="round" stroke-linecap="round"/>@endif
    @foreach($points as $index=>$point)
        @php
            $px=$pointX($index);$py=$pointY($point['value']);$outside=($point['status'] ?? '')==='outside';$repeat=in_array($point['kind'] ?? '',['repeat','correction'],true);
            $date=\Carbon\Carbon::parse($point['measured_at'])->format('d/m/Y H:i');
            $detail=$date.' · '.$displayNumber($point['value']).' '.($level['unit'] ?? '').' · '.($outside?'Fuera de límites':'Dentro de límites').($repeat?' · Repetición / corrección':'').' · '.($point['operator'] ?? '');
        @endphp
        <g class="point" @if(!$pdfMode) tabindex="0" role="button" data-chart-point="{{ $detail }}" aria-label="{{ $detail }}" @endif>
            <title>{{ $detail }}</title>
            @if($outside)
                <path d="M {{ $px-5 }},{{ $py-5 }} L {{ $px+5 }},{{ $py+5 }} M {{ $px+5 }},{{ $py-5 }} L {{ $px-5 }},{{ $py+5 }}" stroke="#ae2439" stroke-width="3.4"/>
            @elseif($repeat)
                <path d="M {{ $px }},{{ $py-6 }} L {{ $px+6 }},{{ $py }} L {{ $px }},{{ $py+6 }} L {{ $px-6 }},{{ $py }} Z" fill="#087452"/>
            @else<circle cx="{{ $px }}" cy="{{ $py }}" r="3.8" fill="#2563a6"/>@endif
            @if(!$pdfMode)<circle cx="{{ $px }}" cy="{{ $py }}" r="12" fill="transparent" stroke="transparent"/>@endif
        </g>
    @endforeach
    @foreach($tickIndexes as $index)<line x1="{{ $pointX($index) }}" x2="{{ $pointX($index) }}" y1="242" y2="248" stroke="#9aabb9"/><text x="{{ $pointX($index) }}" y="267" text-anchor="middle" font-family="DejaVu Sans, sans-serif" font-size="12" fill="#526176">{{ \Carbon\Carbon::parse($points[$index]['measured_at'])->format('d/m') }}</text>@endforeach
    @if(!count($points))<text x="297" y="142" text-anchor="middle" font-family="DejaVu Sans, sans-serif" font-size="15" fill="#526176">Sin lecturas en este periodo</text>@endif
</svg>
