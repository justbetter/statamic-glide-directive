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

        @foreach($srcsets as $type=>$srcset)
            <source
                type="image/{{ $type }}"
                srcset="{{ implode(', ', $srcset) }}"
                sizes="{{ config('justbetter.glide-directive.sizes')[$size] }}"
            >
        @endforeach

        <img 
            {!! $attributes ?? '' !!}
            src="{{ $image->url() }}"  
            alt="{{ $alt }}"
            width="{{ $width }}"
            height="{{ $height }}"
            loading="lazy"
            class="{{ $class ?? '' }}"
            {!! $style ?? '' !!}
        />
    @endif
</picture>