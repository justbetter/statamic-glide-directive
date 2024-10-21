<script>
    window.responsiveResizeObserver = new ResizeObserver((entries) => {
        entries.forEach(entry => {
            const bounds = entry.target.getBoundingClientRect();
            const imgWidth = bounds.width;
            const imgHeight = bounds.height;
            const pixelRatio = window.devicePixelRatio * imgWidth;
            
            requestAnimationFrame(() => entry.target.parentNode.querySelectorAll('source').forEach((source) => {
                source.sizes = pixelRatio + 'px';
            }));
        });
    });
</script>
