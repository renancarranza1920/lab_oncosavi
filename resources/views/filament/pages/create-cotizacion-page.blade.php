<x-filament-panels::page>
    {{ $this->form }}
</x-filament-panels::page>

<script>
    document.addEventListener('livewire:initialized', () => {
        @this.on('open-url-in-new-tab', ({ url }) => {
            window.open(url, '_blank');
        });
    });
</script>