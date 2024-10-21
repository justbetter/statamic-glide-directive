<script>
    window.responsiveResizeObserver = new ResizeObserver((entries) => {
        entries.forEach(entry => {
            const bounds = entry.target.getBoundingClientRect();
            const imgWidth = bounds.width;
            const imgHeight = bounds.height;
            const pixelRatio = window.devicePixelRatio * imgWidth;
            
            entry.target.parentNode.querySelectorAll('source').forEach((source) => {
                requestAnimationFrame(() => source.sizes = pixelRatio + 'px');
            });
        });
    });
</script>
