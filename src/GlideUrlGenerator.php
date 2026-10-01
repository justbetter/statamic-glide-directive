<?php

namespace JustBetter\GlideDirective;

use League\Glide\Signatures\Signature;
use League\Glide\Signatures\SignatureFactory;
use Statamic\Assets\Asset;

class GlideUrlGenerator
{
    protected Signature $signature;

    protected string $assetUrl;

    public function __construct(Asset $asset)
    {
        $this->signature = SignatureFactory::create(config('app.key'));
        $this->assetUrl = $asset->url();
    }

    public function generate(int $width, ?int $height, string $format): string
    {
        $params = $this->signature->addSignature($this->assetUrl, [
            'width' => $width,
            'height' => $height,
            'format' => '.'.$format,
        ]);

        return route('glide-image.preset', [
            ...$params,
            'file' => ltrim($this->assetUrl, '/'),
        ]);
    }
}
