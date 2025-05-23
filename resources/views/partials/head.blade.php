<script>
window.responsiveResizeObserver = new ResizeObserver(async (entries) => {
    entries.forEach(entry => {
        let imgWidth = entry?.devicePixelContentBoxSize?.[0]?.inlineSize || (entry?.borderBoxSize[0]?.inlineSize * window.devicePixelRatio) || 0;
        
        if (imgWidth === 0) {
            return;
        }
        
        requestAnimationFrame(() => {
            if (!entry?.target?.parentNode) {
                return
            }
            
            entry.target.parentNode.querySelectorAll('source').forEach((source) => {
                source.sizes = imgWidth + 'px'
            })
        });
    })
});
</script>
