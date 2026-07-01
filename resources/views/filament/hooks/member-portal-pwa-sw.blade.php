<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('{{ url('/portal/service-worker.js') }}', { scope: '/portal/' }).catch(() => {});
        });
    }
</script>
