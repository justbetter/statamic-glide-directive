<script>
window.responsiveResizeObserver = new ResizeObserver(async (entries) => {
    entries.forEach(entry => {
        let imgWidth = entry?.devicePixelContentBoxSize?.[0]?.inlineSize || 0;

        if (imgWidth === 0) {
            return;
        }
        
        requestAnimationFrame(() => {
            entry.target.loading = 'lazy';
            entry.target.parentNode.querySelectorAll('source').forEach((source) => {
                source.sizes = imgWidth + 'px'
            })
        });
    })
});
</script>
