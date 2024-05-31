<script>
    window.responsiveResizeObserver = new ResizeObserver((entries) => {
        entries.forEach(entry => {
            const imgWidth = entry.target.getBoundingClientRect().width;
            const imgHeight = entry.target.getBoundingClientRect().height;
            const pixelRatio = window.devicePixelRatio * imgWidth;
            
            entry.target.parentNode.querySelectorAll('source').forEach((source) => {
                source.sizes = pixelRatio + 'px';
            });
        });
    });
</script>
