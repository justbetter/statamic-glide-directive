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
            'presets' => Responsive::getPresets($image),
            'class' => $arguments['class'] ?? '',
            'alt' => $arguments['alt'] ?? '',
            'lazy' => $arguments['lazy'] ?? '',
        ]);
    }

    public static function getPresets(Asset $image)
    {
        $config = config('statamic.assets.image_manipulation.presets');

        if (!config('justbetter.glide-directive.placeholder') && isset($config['placeholder'])) {
            unset($config['placeholder']);
        }

        $presets = [];
        $presets['webp'] = '';
        $presets[$image->mimeType()] = '';

        foreach ($config as $preset => $data) {
            $presets['webp'] .= Statamic::tag($preset === 'placeholder' ? 'glide:data_url' : 'glide')->params(['preset' => $preset, 'src' => $image->url(), 'format' => 'webp', 'fit' => 'crop_focal'])->fetch() . ' ' . $data['w'] . 'w,';
            $presets[$image->mimeType()] .= Statamic::tag($preset === 'placeholder' ? 'glide:data_url' : 'glide')->params(['preset' => $preset, 'src' => $image->url(), 'fit' => 'crop_focal'])->fetch() . ' ' . $data['w'] . 'w,';

            if ($preset === 'placeholder') {
                $presets['placeholder'] = Statamic::tag('glide:data_url')->params(['preset' => 'placeholder', 'src' => $image->url(), 'fit' => 'crop_focal'])->fetch();
            }
        }

        if (!isset($presets['placeholder'])) {
            $presets['placeholder'] = Statamic::tag('glide:data_url')->params(['preset' => collect($config)->keys()->first(), 'src' => $image->url(), 'fit' => 'crop_focal'])->fetch();
        }

        return $presets;
    }
}