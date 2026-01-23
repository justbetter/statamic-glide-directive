<script>
window.responsiveResizeObserver = new ResizeObserver(async (entries) => {
    entries.forEach(entry => {
        let imgWidth = null
        imgWidth ||= entry?.contentRect?.width
        imgWidth ||= entry?.borderBoxSize?.[0]?.inlineSize
        imgWidth ||= entry?.contentBoxSize?.[0]?.inlineSize

        if (!imgWidth) {
            return;
        }
        
        requestAnimationFrame(() => {
            if (!entry?.target?.parentNode) {
                return
            }
            
            entry.target.parentNode.querySelectorAll('source').forEach((source) => {
                source.sizes = imgWidth + 'px'
            });
        });
    });
});
</script>
