(() => {
    const plugins = window.filamentChartJsPlugins ??= [];
    if (plugins.some(plugin => plugin.id === 'cci')) return;
    const numberFormat = new Intl.NumberFormat('es-ES', {
        minimumFractionDigits: 2, maximumFractionDigits: 2, useGrouping: false,
    });
    const format = value => numberFormat.format(Number(value));
    const enabled = chart => chart.config.options?.plugins?.cci?.enabled;

    function singleSegments(chart, drawBand) {
        const {ctx, chartArea: area} = chart;
        if (!area) return;
        const count = chart.data.labels.length;
        ctx.save();
        ctx.beginPath(); ctx.rect(area.left, area.top, area.width, area.height); ctx.clip();
        for (let start = 0; start < chart.data.datasets.length; start += 4) {
            const group = chart.data.datasets.slice(start, start + 4);
            if (group.length !== 4) continue;
            const [, expected, low, high] = group;
            const scale = chart.scales[low.yAxisID];
            if (!scale) continue;
            low.data.forEach((value, i) => {
                if (value === null || low.data[i - 1] != null || low.data[i + 1] != null) return;
                const center = chart.scales.x.getPixelForValue(i);
                const left = count === 1 || i === 0 ? area.left : (chart.scales.x.getPixelForValue(i - 1) + center) / 2;
                const right = count === 1 || i === count - 1 ? area.right : (center + chart.scales.x.getPixelForValue(i + 1)) / 2;
                if (drawBand) {
                    if (!chart.isDatasetVisible(start + 2) || !chart.isDatasetVisible(start + 3)) return;
                    const top = scale.getPixelForValue(high.data[i]);
                    ctx.fillStyle = low.backgroundColor;
                    ctx.fillRect(left, top, right - left, scale.getPixelForValue(value) - top);
                } else {
                    [expected, low, high].forEach((series, offset) => {
                        if (!chart.isDatasetVisible(start + offset + 1)) return;
                        const y = scale.getPixelForValue(series.data[i]);
                        ctx.strokeStyle = series.borderColor;
                        ctx.lineWidth = series.borderWidth;
                        ctx.setLineDash(series.borderDash ?? []);
                        ctx.beginPath(); ctx.moveTo(left, y); ctx.lineTo(right, y); ctx.stroke();
                    });
                }
            });
        }
        ctx.restore();
    }

    function legend(chart) {
        const root = chart.canvas.closest('[data-cci-chart]');
        const container = root?.querySelector('[data-cci-legend]');
        if (!container) return;
        root.cciChart = chart;
        const focused = container.contains(document.activeElement) ? document.activeElement.dataset.kind : null;
        const buttons = [];
        for (const kind of ['valor', 'esperado', 'minimo', 'maximo']) {
            const indices = chart.data.datasets.flatMap((series, index) => series.cciKind === kind ? [index] : []);
            if (!indices.length) continue;
            const series = chart.data.datasets[indices[0]];
            const visible = indices.some(index => chart.isDatasetVisible(index));
            const button = document.createElement('button');
            button.type = 'button'; button.className = 'cci-legend-item'; button.dataset.kind = kind;
            button.setAttribute('aria-pressed', String(visible));
            button.setAttribute('aria-label', (visible ? 'Ocultar ' : 'Mostrar ') + series.cciLabel);
            const marker = document.createElement('span');
            marker.className = 'cci-legend-marker'; marker.setAttribute('aria-hidden', 'true');
            marker.style.borderTopColor = series.borderColor;
            marker.style.borderTopStyle = series.borderDash.length ? 'dashed' : 'solid';
            if (kind === 'valor') marker.classList.add('cci-legend-measured');
            const label = document.createElement('span'); label.textContent = series.cciLabel;
            button.append(marker, label);
            button.addEventListener('click', () => {
                indices.forEach(index => chart.setDatasetVisibility(index, !visible));
                chart.update();
            });
            buttons.push(button);
        }
        container.replaceChildren(...buttons);
        if (focused) buttons.find(button => button.dataset.kind === focused)?.focus({preventScroll: true});
    }

    plugins.push({
        id: 'cci',
        beforeInit(chart) {
            if (!enabled(chart)) return;
            const options = chart.config.options;
            for (const [axis, scale] of Object.entries(options.scales ?? {})) {
                if (axis === 'x') continue;
                scale.ticks ??= {};
                scale.ticks.precision = 2;
                scale.ticks.callback = format;
            }
            options.plugins.tooltip ??= {};
            options.plugins.tooltip.filter = item => item.dataset.cciKind === 'valor';
            options.plugins.tooltip.callbacks = {
                label: item => item.dataset.label + ': ' + format(item.parsed.y),
            };
        },
        beforeDraw(chart) { if (enabled(chart)) singleSegments(chart, true); },
        beforeDatasetsDraw(chart) { if (enabled(chart)) singleSegments(chart, false); },
        afterUpdate(chart) { if (enabled(chart)) legend(chart); },
    });
})();
