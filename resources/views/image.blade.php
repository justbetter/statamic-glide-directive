@if($image)
    @if($image->width() > config('justbetter.glide-directive.image_resize_threshold'))
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
                @if(isset($presets['webp']))
                    <source
                        srcset="{{ $presets['webp'] }}"
                        sizes="32px"
                        type="image/webp"
                    >
                @endif
                @if(isset($presets[$image->mimeType()]) && $image->mimeType() !== 'image/webp')
                    <source
                        srcset="{{ $presets[$image->mimeType()] }}"
                        sizes="32px"
                        type="{{ $image->mimeType() }}"
                    >
                @endif
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
    @else
        <img
            {!! $attributes ?? '' !!}
            class="{{ $class }}"
            src="{{ $image->url() }}"
            alt="{{ $alt ?? $image->alt() }}"
            width="{{ $width }}"
            height="{{ $height }}"
            loading="lazy"
        >
    @endif
@endif
