@php($paths = [
    'eye' => 'M2.25 12s3.5-7.5 9.75-7.5S21.75 12 21.75 12s-3.5 7.5-9.75 7.5S2.25 12 2.25 12Zm13 0a3.25 3.25 0 1 1-6.5 0 3.25 3.25 0 0 1 6.5 0Z',
    'download' => 'M12 3v12m-4-4 4 4 4-4M4 15v5h16v-5',
    'filter' => 'M3 4h18l-7 8v7l-4 2v-9L3 4Z',
    'clock' => 'M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM12 7v5l3 2',
    'chart' => 'M4 21V11h4v10m2 0V3h4v18m2 0V7h4v14',
    'search' => 'm21 21-5-5m2-6a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z',
    'expand' => 'M8 3H3v5m0-5 6 6m7-6h5v5m0-5-6 6M3 16v5h5m-5 0 6-6m12 1v5h-5m5 0-6-6',
    'close' => 'm6 6 12 12M6 18 18 6',
])
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="icon"><path d="{{ $paths[$name] ?? '' }}"/></svg>
