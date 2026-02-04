<?php

namespace JustBetter\GlideDirective;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use League\Glide\Signatures\SignatureFactory;
use Statamic\Assets\Asset;
use Statamic\Contracts\Imaging\ImageManipulator;
use Statamic\Facades\Image;
use Statamic\Fields\Value;
use Statamic\Statamic;

class Responsive
{
    public static function handle(mixed ...$arguments): Factory|View|string
    {
        $asset = $arguments[0];
        $asset = $asset instanceof Value ? $asset->value() : $asset;
        $arguments = $arguments[1] ?? [];

        if (! $asset || ! ($asset instanceof Asset)) {
            return '';
        }

        /** @var view-string $view */
        $view = 'statamic-glide-directive::image';

        return view($view, [
            'image' => $asset,
            'default_preset' => self::getDefaultPreset($asset),
            'presets' => self::getPresets($asset, $arguments),
            'attributes' => self::getAttributeBag($arguments),
            'class' => $arguments['class'] ?? '',
            'alt' => $arguments['alt'] ?? ($asset->get('alt') ?? ''),
            'width' => $arguments['width'] ?? $asset->width(),
            'height' => $arguments['height'] ?? $asset->height(),
        ]);
    }

    public static function getPresets(Asset $asset, array $arguments = []): array
    {
        if ($asset->width() <= config('justbetter.glide-directive.image_resize_threshold')) {
            return [];
        }

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
        $configPresets = self::capPresetsByWidth($asset, $configPresets, $arguments);
        $assetMeta = $asset->meta();
        $fit = isset($assetMeta['data']['focus']) ? sprintf('crop-%s', $assetMeta['data']['focus']) : null;

        $webpSourceFound = false;
        $mimeTypeSourceFound = false;
        $webpSource = [];
        $mimeTypeSource = [];

        foreach ($configPresets as $preset => $data) {
            $size = $data['w'].'w';

            if (self::canUseWebpSource()) {
                if ($glideUrl = self::getGlideUrl($asset, $preset, $fit ?? $data['fit'], 'webp')) {
                    $webpSource[] = $glideUrl.' '.$size;

                    if ($preset !== 'placeholder') {
                        $webpSourceFound = true;
                    }
                }
            }

            if (self::canUseMimeTypeSource()) {
                if ($glideUrl = self::getGlideUrl($asset, $preset, $fit ?? $data['fit'], $asset->extension())) {
                    $mimeTypeSource[] = $glideUrl.' '.$size;

                    if ($preset !== 'placeholder') {
                        $mimeTypeSourceFound = true;
                    }
                }
            }

            if ($preset === 'placeholder') {
                if ($glideUrl = Statamic::tag('glide:data_url')->params(['preset' => 'placeholder', 'src' => $asset->url(), 'fit' => $fit ?? $data['fit']])->fetch()) {
                    $presets['placeholder'] = $glideUrl;
                }
            }

        }

        if (self::canUseWebpSource() && count($webpSource) > 0) {
            $presets['webp'] = implode(', ', $webpSource);
        }

        if (self::canUseMimeTypeSource() && count($mimeTypeSource) > 0) {
            $presets[$asset->mimeType()] = implode(', ', $mimeTypeSource);
        }

        if (! $webpSourceFound && ! $mimeTypeSourceFound) {
            $presets = ['placeholder' => $asset->url()];
        }

        if (! isset($presets['placeholder'])) {
            $presets['placeholder'] = Statamic::tag('glide:data_url')->params([
                'preset' => collect($configPresets)->keys()->first(),
                'src' => $asset->url(),
                'fit' => 'crop_focal',
            ])->fetch();
        }

        return array_filter($presets);
    }

    protected static function getDefaultPreset(Asset $asset): ?string
    {
        $assetMeta = $asset->meta();
        $fit = isset($assetMeta['data']['focus']) ? sprintf('crop-%s', $assetMeta['data']['focus']) : null;

        $config = config('statamic.assets.image_manipulation.presets');
        $configPresets = self::getPresetsByRatio($asset, $config);
        $defaultPreset = $configPresets[config('justbetter.glide-directive.default_preset')] ?? false;

        if (! $defaultPreset) {
            return $asset->url();
        }

        return self::getGlideUrl(
            $asset,
            config('justbetter.glide-directive.default_preset', 'sm'),
            $fit ?? ($defaultPreset['fit'] ?? 'contain'),
            self::canUseWebpSource() ? 'webp' : $asset->mimeType()
        );
    }

    protected static function getGlideUrl(Asset $asset, string $preset, string $fit, ?string $format = null): ?string
    {
        if ($preset === 'placeholder') {
            return Statamic::tag('glide:data_url')->params([
                'preset' => $preset,
                'src' => $asset->url(),
                'format' => $format,
                'fit' => $fit,
            ])->fetch();
        }

        $signatureFactory = SignatureFactory::create(config('app.key'));
        $params = $signatureFactory->addSignature($asset->url(), ['p' => $preset, 'fit' => $fit, 'format' => '.'.$format]);

        return route('glide-image.preset', array_merge($params, [
            'file' => ltrim($asset->url(), '/'),
        ]));
    }

    protected static function getManipulator(Asset $item, string $preset, string $fit, ?string $format = null): ImageManipulator|string
    {
        $manipulator = Image::manipulate($item);

        collect(['p' => $preset, 'fm' => $format, 'fit' => $fit])->each(fn ($value, $param) => $manipulator->$param($value));

        return $manipulator;
    }

    protected static function getPresetsByRatio(Asset $asset, array $config): array
    {
        $presets = collect($config);

        [$width, $height] = self::getAssetDimensions($asset);
        $vertical = $height && $width ? $height > $width : false;
        $presets = $presets->filter(fn ($preset, $key) => $key === 'placeholder' || (isset($preset['w'], $preset['h']) && (($preset['h'] > $preset['w']) === $vertical)));

        return $presets->isNotEmpty() ? $presets->toArray() : $config;
    }

    protected static function getAttributeBag(array $arguments): string
    {
        $excludedAttributes = ['src', 'class', 'alt', 'width', 'height', 'onload', 'max_width', 'rendered_width'];

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

    protected static function capPresetsByWidth(Asset $asset, array $presets, array $arguments = []): array
    {
        $maxWidth = self::getMaxSrcsetWidth($asset, $arguments);

        return collect($presets)
            ->filter(function ($preset, $key) use ($maxWidth) {
                if ($key === 'placeholder') {
                    return true;
                }

                if (! isset($preset['w'])) {
                    return false;
                }

                return (int) $preset['w'] <= $maxWidth;
            })
            ->toArray();
    }

    protected static function getMaxSrcsetWidth(Asset $asset, array $arguments = []): ?int
    {
        [$width] = self::getAssetDimensions($asset);
        $maxWidth = $width ?: null;

        $renderedWidth = self::getRenderedWidth($arguments);
        if ($renderedWidth) {
            $renderedCap = $renderedWidth * 2;
            $maxWidth = $maxWidth ? min($maxWidth, $renderedCap) : $renderedCap;
        }

        return $maxWidth;
    }

    protected static function getRenderedWidth(array $arguments): ?int
    {
        $renderedWidth = $arguments['max_width'] ?? $arguments['rendered_width'] ?? null;

        if ($renderedWidth === null) {
            return null;
        }

        $renderedWidth = (int) $renderedWidth;

        return $renderedWidth > 0 ? $renderedWidth : null;
    }

    protected static function getAssetDimensions(Asset $asset): array
    {
        $width = $asset->width();
        $height = $asset->height();

        return [$width, $height];
    }
}
