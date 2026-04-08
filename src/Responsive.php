<?php

namespace JustBetter\GlideDirective;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use League\Glide\Signatures\SignatureFactory;
use Statamic\Assets\Asset;
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

        $sizes = $arguments['sizes'] ?? config('justbetter.glide-directive.sizes_default');
        $focus = $asset->get('focus');

        if (is_string($focus)) {
            $styleAttr = sprintf(' style="object-position: %s"', self::focusToPosition($focus));
        }

        /** @var view-string $view */
        $view = 'statamic-glide-directive::image';

        return view($view, [
            'image' => $asset,
            'srcsets' => self::buildSrcsets($asset, $arguments['ratio'] ?? null, $arguments['width'] ?? null, $arguments['height'] ?? null),
            'attributes' => self::getAttributeBag($arguments),
            'class' => $arguments['class'] ?? '',
            'alt' => $arguments['alt'] ?? ($asset->get('alt') ?? ''),
            'width' => $arguments['width'] ?? $asset->width(),
            'height' => $arguments['height'] ?? $asset->height(),
            'styleAttr' => $styleAttr ?? '',
            'sizes' => $sizes,
        ]);
    }

    protected static function focusToPosition(string $focus): string
    {
        if (! str_contains($focus, '-')) {
            return $focus;
        }

        return vsprintf('%d%% %d%%', explode('-', $focus));
    }

    protected static function cropAndResize(Asset $asset, int $width, int $height): array
    {
        $formats = config('justbetter.glide-directive.default_formats');

        $srcsetParts = [];
        foreach ($formats as $format => $mimeType) {
            $srcsetParts[$format] = [];
            
                $url = self::getGlideUrl($asset, $width, $height, $format);

                $url = url()->query($url, ['crop' => 1]);
                $srcsetParts[$format][] = "{$url} {$width}w";

                $url = self::getGlideUrl($asset, $width * 2, $height * 2, $format);
                $url = url()->query($url, ['crop' => 1]);

                $width = $width * 2;
                $srcsetParts[$format][] = "{$url} {$width}w";
        }

        return $srcsetParts;
    }

    protected static function buildSrcsets(Asset $asset, ?float $ratio, ?int $width = null, ?int $height = null): array
    {
        if ($width && $height) {
            return self::cropAndResize($asset, $width, $height);
        }

        return self::getSrcsets($asset, $ratio);
    }

    protected static function getSrcSets(Asset $asset, ?float $ratio)
    {
        $formats = config('justbetter.glide-directive.default_formats');
        $originalRatio = $asset->height() && $asset->width()
            ? $asset->height() / $asset->width()
            : null;

        $useRatio = $ratio ?? $originalRatio;


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

    protected static function getAttributeBag(array $arguments): string
    {
        $excludedAttributes = ['src', 'class', 'alt', 'width', 'height', 'onload', 'max_width', 'rendered_width'];

        return collect($arguments)
            ->filter(fn ($value, $key) => ! in_array($key, $excludedAttributes))
            ->map(function ($value, $key) {
                return $key.'="'.$value.'"';
            })->implode(' ');
    }
}
