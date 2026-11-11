const recordChecks = [...document.querySelectorAll('[data-record-select]')];
const allCheck = document.getElementById('select-all');
const exportSelection = document.getElementById('pdf-selected');
function updateSelection() {
    const count = recordChecks.filter(input => input.checked).length;
    document.getElementById('selection-count').textContent = count;
    exportSelection.disabled = count === 0;
    allCheck.checked = count > 0 && count === recordChecks.length;
    allCheck.indeterminate = count > 0 && count < recordChecks.length;
}
recordChecks.forEach(input => input.addEventListener('change', updateSelection));
allCheck?.addEventListener('change', () => {
    recordChecks.forEach(input => { input.checked = allCheck.checked; });
    updateSelection();
});
document.getElementById('include-graphs')?.addEventListener('change', event => {
    document.querySelectorAll('[data-include-graphs]').forEach(input => { input.value = event.target.checked ? '1' : '0'; });
});
document.querySelectorAll('[data-dialog]').forEach(button => button.addEventListener('click', () => {
    document.getElementById(button.dataset.dialog).showModal();
}));
document.querySelectorAll('[data-close-dialog]').forEach(button => button.addEventListener('click', () => {
    button.closest('dialog').close();
}));
document.querySelectorAll('[data-go-graphs]').forEach(link => link.addEventListener('click', () => link.closest('dialog').close()));
document.getElementById('chart-area')?.addEventListener('change', event => {
    document.getElementById('chart-test').value = '';
    event.target.form.requestSubmit();
});
document.addEventListener('click', event => {
    document.querySelectorAll('.filter-menu[open]').forEach(menu => {
        if (!menu.contains(event.target)) menu.open = false;
    });
});
document.addEventListener('keydown', event => {
    if (event.key === 'Escape') document.querySelectorAll('.filter-menu[open]').forEach(menu => { menu.open = false; });
});
const graphGrid = document.querySelector('[data-chart-module]');
if (graphGrid) {
    try {
        // Reutiliza el motor de gráficos instalado con Filament; esta página no inicia Livewire ni una sesión del panel.
        const { default: createChart } = await import(graphGrid.dataset.chartModule);
        function drawChart(root, config) {
            const component = createChart(structuredClone(config));
            component.$el = root;
            component.$refs = {
                canvas: root.querySelector('canvas'),
                backgroundColorElement: root.querySelector('[data-chart-color="background"]'),
                borderColorElement: root.querySelector('[data-chart-color="border"]'),
                textColorElement: root.querySelector('[data-chart-color="text"]'),
                gridColorElement: root.querySelector('[data-chart-color="grid"]'),
            };
            return component.initChart();
        }
        document.querySelectorAll('[data-chart]').forEach(root => {
            const config = JSON.parse(document.getElementById(root.dataset.chart).textContent);
            root.cciChart = drawChart(root, config);
        });
        const dialog = document.getElementById('chart-expanded');
        let expandedChart;
        document.querySelectorAll('[data-expand-chart]').forEach(button => button.addEventListener('click', () => {
            const config = JSON.parse(document.getElementById(button.dataset.expandChart).textContent);
            const card = button.closest('.chart-card');
            document.getElementById('expanded-title').textContent = card.querySelector('h3').textContent + ' · ' + card.querySelector('.muted').textContent;
            dialog.showModal();
            expandedChart = drawChart(dialog.querySelector('.chart-canvas'), config);
        }));
        dialog.addEventListener('close', () => { expandedChart?.destroy(); expandedChart = null; });
    } catch (error) {
        graphGrid.querySelectorAll('.chart-canvas').forEach(root => {
            root.innerHTML = '<p class="errors">No se pudo cargar el gráfico. Recarga la página para volver a intentarlo.</p>';
        });
        console.error('No se pudieron cargar los gráficos de supervisión.', error);
    }
}

function updateNavigation() {
    const target = location.hash === '#graficos' ? '#graficos' : '#historial';
    document.querySelectorAll('.topbar nav a').forEach(link => link.classList.toggle('active', link.getAttribute('href') === target));
}
window.addEventListener('hashchange', updateNavigation);
updateNavigation();
