@if($image)
    <picture>
        @if( $image->extension() == 'svg' || $image->extension() == 'gif')
            <img
                class="{{ $class }}"
                src="{{ $image->url() }}"
                alt="{{ $alt }}"
                width="{{ $width }}"
                height="{{ $height }}"
            />
        @else
            @isset($presets['webp'])
                <source
                    srcset="{{ $presets['webp'] }}"
                    sizes="32px"
                    type="image/webp"
                >
            @endisset
            @isset($presets[$image->mimeType()])
                <source
                    srcset="{{ $presets[$image->mimeType()] }}"
                    sizes="32px"
                    type="{{ $image->mimeType() }}"
                >
            @endisset
            <img
                    {!! $attributes ?? '' !!}
                    class="{{ $class }}"
                    src="{{ $presets['placeholder'] ?? $image->url() }}"
                    alt="{{ $alt ?? $image->alt() }}"
                    width="{{ $width }}"
                    height="{{ $height }}"
                    loading="lazy"
                    onload="
                        this.onload = null;
                        window.responsiveResizeObserver.observe(this);
                    "
            >
        @endif
    </picture>
@endif
