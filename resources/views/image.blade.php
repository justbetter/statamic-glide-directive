@if($image)
    <picture>
        @if( $image->extension() == 'svg' || $image->extension() == 'gif')
        <img class="{{ $class }}" src="{{ $image->url() }}" alt="{{ $alt }}"
             @if($lazy)
                 loading="lazy"
             @endif
        />
        @else
            <source
                    srcset="{{ $presets['webp'] }}"
                    sizes="32px"
                    type="image/webp"
            >
            <source
                    srcset="{{ $presets[$image->mimeType()] }}"
                    sizes="32px"
                    type="{{ $image->mimeType() }}"
            >
            <img
                class="{{ $class }}"
                src="{{ $presets['placeholder'] }}"
                alt="{{ $alt ?? $image->alt() }}"
                width="{{ $image->width() }}"
                height="{{ $image->height() }}"
                onload="
                    this.onload=null;
                    window.responsiveResizeObserver.observe(this);
                "
                @if($lazy)
                    loading="lazy"
                @endif
            >
        @endif
    </picture>
@endif
