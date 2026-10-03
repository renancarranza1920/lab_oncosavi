document.getElementById('portal-theme')?.addEventListener('click', () => {
    const oscuro = document.documentElement.classList.toggle('dark');
    try { localStorage.setItem('theme', oscuro ? 'dark' : 'light'); } catch (_) {}
});

const visor = document.getElementById('portal-pdf-viewer');
if (visor && typeof visor.showModal === 'function') {
    const frame = document.getElementById('portal-pdf-frame');
    document.querySelectorAll('[data-portal-pdf]').forEach(enlace => {
        enlace.addEventListener('click', event => {
            event.preventDefault();
            document.getElementById('portal-pdf-title').textContent = enlace.dataset.portalPdf;
            document.getElementById('portal-pdf-open').href = enlace.href;
            frame.src = enlace.href;
            visor.showModal();
        });
    });
    document.getElementById('portal-pdf-close').addEventListener('click', () => visor.close());
    visor.addEventListener('close', () => { frame.src = 'about:blank'; });
}
