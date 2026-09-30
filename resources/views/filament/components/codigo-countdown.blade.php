<script>
document.addEventListener("DOMContentLoaded", function() {
    function updateCountdowns() {
        document.querySelectorAll('.countdown').forEach(function(el) {
            let timestamp = el.dataset.timestamp * 1000;
            let now = new Date().getTime();
            let distance = timestamp - now;

            if (distance <= 0) {
                el.innerHTML = 'Expirado';
                return;
            }

            let days = Math.floor(distance / (1000 * 60 * 60 * 24));
            let hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            let minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            let seconds = Math.floor((distance % (1000 * 60)) / 1000);

            el.innerHTML = `${days}d ${hours}h ${minutes}m ${seconds}s`;
        });
    }

    setInterval(updateCountdowns, 1000);
    updateCountdowns();
});
</script>
