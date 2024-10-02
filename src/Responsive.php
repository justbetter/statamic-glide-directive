<?php

namespace JustBetter\GlideDirective;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Statamic\Assets\Asset;
use Statamic\Fields\Value;
use Statamic\Statamic;

class Responsive
{
    public static function handle(mixed ...$arguments): Factory|View|string
    {
        $image = $arguments[0];
        $image = $image instanceof Value ? $image->value() : $image;
        $arguments = $arguments[1] ?? [];

        if (! $image || !($image instanceof Asset)) {
            return '';
        }

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

    public static function getPresets(Asset $image): array
    {
        $config = config('statamic.assets.image_manipulation.presets');

        if (! config('justbetter.glide-directive.placeholder') && isset($config['placeholder'])) {
            unset($config['placeholder']);
        }

        $presets = [];

        if (self::canUseWebpSource()) {
            $presets['webp'] = '';
        }

        if (self::canUseMimeTypeSource()) {
            $presets[$image->mimeType()] = '';
        }

        $configPresets = self::getPresetsByRatio($image, $config);
        $imageMeta = $image->meta();
        $fit = isset($imageMeta['data']['focus']) ? sprintf('crop-%s', $imageMeta['data']['focus']) : null;
        $index = 0;
        foreach ($configPresets as $preset => $data) {
            $size = $data['w'].'w';

            if ($index < (count($configPresets) - 1)) {
                $size .= ', ';
            }

            if (self::canUseWebpSource()) {
                $glideUrl = Statamic::tag($preset === 'placeholder' ? 'glide:data_url' : 'glide')->params(['preset' => $preset, 'src' => $image->url(), 'format' => 'webp', 'fit' => $fit ?? $data['fit']])->fetch();
                if ($glideUrl) {
                    $presets['webp'] .= $glideUrl.' '.$size;
                }
            }

            if (self::canUseMimeTypeSource()) {
                $glideUrl = Statamic::tag($preset === 'placeholder' ? 'glide:data_url' : 'glide')->params(['preset' => $preset, 'src' => $image->url(), 'fit' => $fit ?? $data['fit']])->fetch();
                if ($glideUrl) {
                    $presets[$image->mimeType()] .= $glideUrl.' '.$size;
                }
            }

            if ($preset === 'placeholder') {
                $glideUrl = Statamic::tag('glide:data_url')->params(['preset' => 'placeholder', 'src' => $image->url(), 'fit' => $fit ?? $data['fit']])->fetch();
                if ($glideUrl) {
                    $presets['placeholder'] = $glideUrl;
                }
            }

            $index++;
        }

        if (! isset($presets['placeholder'])) {
            $glideUrl = Statamic::tag('glide:data_url')->params(['preset' => collect($configPresets)->keys()->first(), 'src' => $image->url(), 'fit' => 'crop_focal'])->fetch();
            $presets['placeholder'] = $glideUrl;
        }

        return $presets;
    }

    protected static function getPresetsByRatio(Asset $image, array $config): array
    {
        $ratio = $image->width() / $image->height();
        $presets = collect($config);

        // filter config based on aspect ratio
        // if ratio < 1 get all presets with a height bigger than the width, else get all presets with width equal or bigger than the height.
        if ($ratio < 0.999) {
            $presets = $presets->filter(fn ($preset, $key) => $preset['h'] > $preset['w']);
            if ($presets->isNotEmpty() && isset($config['placeholder'])) {
                $presets->prepend($config['placeholder'], 'placeholder');
            }
        } else {
            $presets = $presets->filter(fn ($preset, $key) => $key === 'placeholder' || $preset['w'] >= $preset['h']);
        }

        return $presets->isNotEmpty() ? $presets->toArray() : $config;
    }

    protected static function getAttributeBag(array $arguments): string
    {
        $excludedAttributes = ['src', 'class', 'alt', 'width', 'height', 'onload'];

        return collect($arguments)
            ->filter(fn ($value, $key) => ! in_array($key, $excludedAttributes))
            ->map(function ($value, $key) {
                return $key.'="'.$value.'"';
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
