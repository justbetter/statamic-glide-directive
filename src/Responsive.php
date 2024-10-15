<?php

namespace JustBetter\GlideDirective;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use JustBetter\GlideDirective\Jobs\GenerateGlideImageJob;
use Statamic\Assets\Asset;
use Statamic\Contracts\Assets\Asset as AssetContract;
use Statamic\Facades\Config;
use Statamic\Facades\Glide;
use Statamic\Facades\Image;
use Statamic\Facades\URL;
use Statamic\Fields\Value;
use Statamic\Imaging\GlideImageManipulator;
use Statamic\Imaging\ImageGenerator;
use Statamic\Statamic;
use Statamic\Support\Str;

class Responsive
{
    public function __construct(public GlideImageManipulator $glideImageManipulator)
    {
    }

    public static function handle(mixed ...$arguments): Factory|View|string
    {
        $asset = $arguments[0];
        $asset = $asset instanceof Value ? $asset->value() : $asset;
        $arguments = $arguments[1] ?? [];

        if (! $asset || ! ($asset instanceof Asset)) {
            return '';
        }

        return view('statamic-glide-directive::image', [
            'image' => $asset,
            'presets' => self::getPresets($asset),
            'attributes' => self::getAttributeBag($arguments),
            'class' => $arguments['class'] ?? '',
            'alt' => $arguments['alt'] ?? '',
            'width' => $arguments['width'] ?? $asset->width(),
            'height' => $arguments['height'] ?? $asset->height(),
        ]);
    }

    public static function getPresets(Asset $asset): array
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
            $presets[$asset->mimeType()] = '';
        }

        $configPresets = self::getPresetsByRatio($asset, $config);
        $assetMeta = $asset->meta();
        $fit = isset($assetMeta['data']['focus']) ? sprintf('crop-%s', $assetMeta['data']['focus']) : null;
        $index = 0;

        foreach ($configPresets as $preset => $data) {
            $size = $data['w'].'w';

            if ($index < (count($configPresets) - 1)) {
                $size .= ', ';
            }

            if (self::canUseWebpSource() && $glideUrl = self::getGlideUrl($asset, $preset, $fit ?? $data['fit'], 'webp')) {
                $presets['webp'] .= $glideUrl.' '.$size;
            }

            if (self::canUseMimeTypeSource() && $glideUrl = self::getGlideUrl($asset, $fit ?? $data['fit'], $preset)) {
                $presets[$asset->mimeType()] .= $glideUrl.' '.$size;
            }

            if ($preset === 'placeholder') {
                $glideUrl = Statamic::tag('glide:data_url')->params(['preset' => 'placeholder', 'src' => $asset->url(), 'fit' => $fit ?? $data['fit']])->fetch();
                if ($glideUrl) {
                    $presets['placeholder'] = $glideUrl;
                }
            }

            $index++;
        }

        if (! isset($presets['placeholder'])) {
            $glideUrl = Statamic::tag('glide:data_url')->params(['preset' => collect($configPresets)->keys()->first(), 'src' => $asset->url(), 'fit' => 'crop_focal'])->fetch();
            $presets['placeholder'] = $glideUrl;
        }

        return $presets;
    }

    protected static function getGlideUrl(Asset $asset, string $preset, string $fit, ?string $format = null): ?string
    {
        if ($preset === 'placeholder') {
            return Statamic::tag('glide:data_url')->params(['preset' => $preset, 'src' => $asset->url(), 'format' => $format, 'fit' => $fit])->fetch();
        }

        $manipulator = self::getManipulator($asset, $preset, $fit, $format);
        $params = $manipulator->getParams();

        $manipulationCacheKey = 'asset::' . $asset->id() . '::' . md5(json_encode($params));

        if (Glide::cacheStore()->has($manipulationCacheKey)) {
            $cachedUrl = Glide::cacheStore()->get($manipulationCacheKey);
            $url = Str::ensureLeft(config('statamic.assets.image_manipulation.route'), '/') . '/' . $cachedUrl;

            return URL::encode($url);
        }

        GenerateGlideImageJob::dispatch($asset, $preset, $fit, $format);

        return null;
    }

    protected static function getManipulator(Asset $item, string $preset, string $fit, ?string $format = null): GlideImageManipulator
    {
        $manipulator = Image::manipulate($item);

        collect(['p' => $preset, 'fm' => $format, 'fit' => $fit])->each(function ($value, $param) use ($manipulator) {
            $manipulator->$param($value);
        });

        return $manipulator;
    }

    protected static function getPresetsByRatio(Asset $asset, array $config): array
    {
        $presets = collect($config);

        // filter config based on aspect ratio
        $vertical = $asset->height() > $asset->width();
        $presets = $presets->filter(fn ($preset, $key) => $key === 'placeholder' || (($preset['h'] > $preset['w']) === $vertical));

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
