<script>
    window.responsiveResizeObserver = new ResizeObserver((entries) => {
        console.log('test')
        entries.forEach(entry => {
            const imgWidth = entry.target.getBoundingClientRect().width;
            entry.target.parentNode.querySelectorAll('source').forEach((source) => {
                source.sizes = imgWidth + 'px';
            });
        });

    });
</script>