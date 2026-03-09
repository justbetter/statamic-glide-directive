<?php

namespace JustBetter\GlideDirective;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use League\Glide\Signatures\SignatureFactory;
use Statamic\Assets\Asset;
use Statamic\Contracts\Imaging\ImageManipulator;
use Statamic\Facades\Image;
use Statamic\Fields\Value;

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

        $size = $arguments['size'] ?? config('justbetter.glide-directive.default_preset');
        $focus = $asset->get('focus');
        $cover = isset($arguments['cover']);
        $contain = isset($arguments['contain']);

        $classAttr = match (true) {
            $cover => 'object-cover w-full h-full object-[var(--focal-point)]'.(isset($arguments['class']) ? ' '.e($arguments['class']) : ''),
            $contain => 'object-contain w-full h-full object-[var(--focal-point)]'.(isset($arguments['class']) ? ' '.e($arguments['class']) : ''),
            default => e($arguments['class'] ?? ''),
        };

        if (($cover || $contain) && is_string($focus)) {
            $styleAttr = sprintf(' style="object-position: %s"', self::focusToPosition($focus));
        }

        /** @var view-string $view */
        $view = 'statamic-glide-directive::image';

        return view($view, [
            'image' => $asset,
            'srcsets' => self::buildSrcsets($asset, $arguments['ratio'] ?? null),
            'attributes' => self::getAttributeBag($arguments),
            'class' => $arguments['class'] ?? '',
            'alt' => $arguments['alt'] ?? ($asset->get('alt') ?? ''),
            'width' => $arguments['width'] ?? $asset->width(),
            'height' => $arguments['height'] ?? $asset->height(),
            'classAttr' => $classAttr,
            'styleAttr' => $styleAttr ?? '',
            'size' => $size,

        ]);
    }

    protected static function focusToPosition(string $focus): string
    {
        if (! str_contains($focus, '-')) {
            return $focus;
        }

        return vsprintf('%d%% %d%%', explode('-', $focus));
    }

    protected static function buildSrcsets(Asset $asset, ?float $ratio): array
    {
        $originalRatio = $asset->height() && $asset->width()
            ? $asset->height() / $asset->width()
            : null;

        $useRatio = $ratio ?? $originalRatio;

        $formats = config('justbetter.glide-directive.default_formats');
        $srcsetParts = [];
        foreach ($formats as $format => $mimeType) {
            $srcsetParts[$format] = [];

            foreach (self::getWidths() as $width) {
                $height = $useRatio ? (int) round($width * $useRatio) : null;

                $srcset = [
                    'width' => $width,
                    'height' => $height,
                    'ratio' => $useRatio,
                ];

                $url = self::getGlideUrl($asset, $width, $height, $format);
                $srcsetParts[$format][] = "{$url} {$srcset['width']}w";
            }
        }

        return $srcsetParts;
    }

    protected static function getWidths(): array
    {
        return config('justbetter.glide-directive.default_widths', [
            320,
            480,
            640,
            768,
            1024,
            1280,
            1440,
            1536,
            1680,
        ]);
    }

    public static function getGlideUrl(Asset $asset, int $width, ?int $height, string $format): ?string
    {
        $signatureFactory = SignatureFactory::create(config('app.key'));

        $params = $signatureFactory->addSignature($asset->url(), ['width' => $width, 'height' => $height, 'format' => '.'.$format]);

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
