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

        $size = $arguments['size'] ?? config('justbetter.glide-directive.default_preset');
        $focus = $asset->get('focus');
        $cover = isset($arguments['cover']);
        $contain = isset($arguments['contain']);

        $classAttr = match (true) {
            $cover => 'object-cover w-full h-full object-[var(--focal-point)]'.(isset($arguments['class']) ? ' '.e($arguments['class']) : ''),
            $contain => 'object-contain w-full h-full object-[var(--focal-point)]'.(isset($arguments['class']) ? ' '.e($arguments['class']) : ''),
            default => e($arguments['class'] ?? ''),
        };

        if (($cover || $contain) || is_string($focus)) {
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
