<?php

namespace JustBetter\GlideDirective;

use Statamic\Assets\Asset;
use Statamic\Statamic;

class Responsive
{
    public static function handle(...$arguments)
    {
        $image = $arguments[0];
        $image = get_class($image) === 'Statamic\Fields\Value' ? $image->value() : $image;
        $arguments = $arguments[1] ?? [];

        return view('statamic-glide-directive::image', [
            'image' => $image,
            'presets' => self::getPresets($image),
            'attributes' => self::getAttributeBag($arguments),
            'class' => $arguments['class'] ?? '',
            'alt' => $arguments['alt'] ?? '',
            'width' => $arguments['width'] ?? $image->width(),
            'height' => $arguments['height'] ?? $image->height(),
        ]);
    }

    public static function getPresets(Asset $image)
    {
        $config = config('statamic.assets.image_manipulation.presets');

        if (!config('justbetter.glide-directive.placeholder') && isset($config['placeholder'])) {
            unset($config['placeholder']);
        }

        $presets = [];

        if (self::canUseWebpSource()) {
            $presets['webp'] = '';
        }

        if (self::canUseMimeTypeSource()) {
            $presets[$image->mimeType()] = '';
        }

        $index = 0;

        foreach ($config as $preset => $data) {
            $size = $data['w'] . 'w';

            if ($index < (count($config) - 1)) {
                $size .= ', ';
            }

            if (self::canUseWebpSource()) {
                $presets['webp'] .= Statamic::tag($preset === 'placeholder' ? 'glide:data_url' : 'glide')->params(['preset' => $preset, 'src' => $image->url(), 'format' => 'webp', 'fit' => $data['crop'] ?? 'crop_focal'])->fetch() . ' ' . $size;
            }

            if (self::canUseMimeTypeSource()) {
                $presets[$image->mimeType()] .= Statamic::tag($preset === 'placeholder' ? 'glide:data_url' : 'glide')->params(['preset' => $preset, 'src' => $image->url(), 'fit' => $data['crop'] ?? 'crop_focal'])->fetch() . ' ' . $size;
            }


            if ($preset === 'placeholder') {
                $presets['placeholder'] = Statamic::tag('glide:data_url')->params(['preset' => 'placeholder', 'src' => $image->url(), 'fit' => $data['crop'] ?? 'crop_focal'])->fetch();
            }

            $index++;
        }

        if (!isset($presets['placeholder'])) {
            $presets['placeholder'] = Statamic::tag('glide:data_url')->params(['preset' => collect($config)->keys()->first(), 'src' => $image->url(), 'fit' => 'crop_focal'])->fetch();
        }

        return $presets;
    }

    protected static function getAttributeBag(array $arguments): string
    {
        $excludedAttributes = ['src', 'class', 'alt', 'width', 'height', 'onload'];

        return collect($arguments)
            ->filter(fn ($value, $key) => !in_array($key, $excludedAttributes))
            ->map(function ($value, $key) {
                return $key . '="' . $value . '"';
            })->implode(' ');
    }

    protected static function canUseWebpSource(): bool
    {
        return in_array(config('justbetter.glide-directive.sources'), ['webp', 'both']);
    }

    protected static function canUseMimeTypeSource(): bool
    {
        return in_array(config('justbetter.glide-directive.sources'), ['mime_type', 'both']);
    }
}
