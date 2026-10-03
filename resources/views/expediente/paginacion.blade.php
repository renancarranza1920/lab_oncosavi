@if ($registros->hasPages())
    <nav class="portal-pagination" aria-label="Paginación">
        @if ($registros->onFirstPage())<span class="portal-button portal-disabled">Anterior</span>@else<a class="portal-button portal-button-quiet" href="{{ $registros->previousPageUrl() }}" rel="prev">Anterior</a>@endif
        <span class="portal-muted portal-small">Página {{ $registros->currentPage() }} de {{ $registros->lastPage() }}</span>
        @if ($registros->hasMorePages())<a class="portal-button portal-button-quiet" href="{{ $registros->nextPageUrl() }}" rel="next">Siguiente</a>@else<span class="portal-button portal-disabled">Siguiente</span>@endif
    </nav>
@endif
