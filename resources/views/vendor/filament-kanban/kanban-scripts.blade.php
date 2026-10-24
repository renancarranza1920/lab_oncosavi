<script>
(() => {
    const moduleUrl = @js(asset('js/sign-message.js').'?v='.substr(hash_file('sha256', public_path('js/sign-message.js')), 0, 16));
    const config = {
        sdkUrl: @js(asset('js/qz-tray.js').'?v='.substr(hash_file('sha256', public_path('js/qz-tray.js')), 0, 16)),
        certificateUrl: @js(route('qz.certificado')),
        signatureUrl: @js(route('qz.firmar')),
    };
    const statuses = @js($statuses->pluck('id')->values()->toArray());
    const printingEnabled = @js((bool) (config('laboratorio.impresion_etiquetas_habilitada') && auth()->user()?->can('imprimir_etiquetas_kanban')));

    function loadSigningModule() {
        if (window.lameriQzModuleUrl && window.lameriQzModuleUrl !== moduleUrl) {
            window.location.reload();
            return Promise.reject(new Error('Se actualizó la impresión. Recargando la página.'));
        }
        if (window.lameriQz) return Promise.resolve(window.lameriQz);

        if (!window.lameriQzModulePromise) {
            window.lameriQzModuleUrl = moduleUrl;
            window.lameriQzModulePromise = new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.id = 'lameri-qz-signing';
                script.src = moduleUrl;
                script.onload = () => window.lameriQz
                    ? resolve(window.lameriQz)
                    : reject(new Error('No se pudo preparar la firma de las etiquetas.'));
                script.onerror = () => {
                    script.remove();
                    reject(new Error('No se pudo cargar la firma de las etiquetas.'));
                };
                document.head.appendChild(script);
            }).catch(error => {
                window.lameriQzModulePromise = null;
                throw error;
            });
        }
        return window.lameriQzModulePromise;
    }

    // Los scripts externos se cargan una sola vez en head y se esperan antes
    // de configurar QZ. Livewire no puede reemplazar después el SDK configurado.
    if (printingEnabled) {
        loadSigningModule().then(client => client.init(config)).catch(() => {
            // El botón de impresión vuelve a intentar y muestra el error al usuario.
        });
    }

    window.lameriKanbanCleanup?.();
    window.lameriZplPrintCleanup?.();
    let stopped = false;
    let removePrintListener;
    const sortables = [];

    function registerPrintListener() {
        if (!printingEnabled || stopped || removePrintListener || !window.Livewire) return;
        removePrintListener = Livewire.on('imprimirZplFrontend', eventData => {
            const zpl = eventData?.zpl || eventData?.[0]?.zpl;
            loadSigningModule()
                .then(client => client.init(config).then(() => client.print(zpl)))
                .catch(error => alert('No se pudieron imprimir las etiquetas: ' + error.message));
        });
    }

    function setupSortables() {
        if (stopped || !window.Sortable) return;
        statuses.forEach(status => {
            const container = document.querySelector(`[data-status-id='${status}']`);
            if (!container || Sortable.get(container)) return;
            sortables.push(Sortable.create(container, {
                group: 'filament-kanban',
                ghostClass: 'opacity-50',
                animation: 150,
                onStart: () => document.body.classList.add('grabbing'),
                onEnd: () => document.body.classList.remove('grabbing'),
                setData: (dataTransfer, element) => dataTransfer.setData('id', element.id),
                onAdd: event => Livewire.dispatch('status-changed', {
                    recordId: event.item.id,
                    status: event.to.dataset.statusId,
                    fromOrderedIds: Array.from(event.from.children).map(child => child.id),
                    toOrderedIds: Array.from(event.to.children).map(child => child.id),
                }),
                onUpdate: event => Livewire.dispatch('sort-changed', {
                    recordId: event.item.id,
                    status: event.from.dataset.statusId,
                    orderedIds: Array.from(event.from.children).map(child => child.id),
                }),
            }));
        });
    }

    function cleanup() {
        if (stopped) return;
        stopped = true;
        removePrintListener?.();
        sortables.forEach(sortable => sortable.destroy());
        document.body.classList.remove('grabbing');
        document.removeEventListener('livewire:init', registerPrintListener);
        document.removeEventListener('livewire:navigated', setupSortables);
        document.removeEventListener('livewire:navigating', cleanup);
    }

    window.lameriKanbanCleanup = cleanup;
    registerPrintListener();
    setupSortables();
    document.addEventListener('livewire:init', registerPrintListener, { once: true });
    document.addEventListener('livewire:navigated', setupSortables);
    document.addEventListener('livewire:navigating', cleanup, { once: true });
})();
</script>
