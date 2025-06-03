<script>
window.responsiveResizeObserver = new ResizeObserver(async (entries) => {
    entries.forEach(entry => {
        let imgWidth = null
        imgWidth ||= entry?.devicePixelContentBoxSize?.[0]?.inlineSize
        imgWidth ||= entry?.borderBoxSize?.[0]?.inlineSize * window.devicePixelRatio
        imgWidth ||= entry?.contentBoxSize?.[0]?.inlineSize * window.devicePixelRatio
        imgWidth ||= entry?.contentRect?.width * window.devicePixelRatio

        if (!imgWidth) {
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
