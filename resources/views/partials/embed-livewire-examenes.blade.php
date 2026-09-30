

{{-- <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script> --}}

<div>
    {{-- imprimir lo que trae --}}
@livewire('examen-drag-drop', ['perfilId' => $perfilId], key('examen-drag-drop-' . uniqid()))




    @push('scripts')
    <script>
document.addEventListener('livewire:initialized', () => {
    Livewire.on('examenesSeleccionadosUpdated', ({ examenes }) => {
        const formComponents = Array.from(document.querySelectorAll('[wire\\:id]'));
        
        formComponents.forEach(component => {
            const formComponent = Livewire.find(component.getAttribute('wire:id'));
             // Log para ver el contenido de formComponents
             console.log('formComponents:', formComponents);
            if (formComponent) {
                formComponent.set('data.examenes_seleccionados', JSON.stringify(examenes), false);
            }
        });
    });
});
</script>
    @endpush
</div>
