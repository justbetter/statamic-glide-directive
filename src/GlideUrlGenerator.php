<?php

namespace JustBetter\GlideDirective;

use League\Glide\Signatures\Signature;
use League\Glide\Signatures\SignatureFactory;
use Statamic\Assets\Asset;

class GlideUrlGenerator
{
    protected Signature $signature;

    protected string $assetUrl;

    protected string $template;

    public function __construct(Asset $asset)
    {
        $this->signature = SignatureFactory::create(config('app.key'));
        $this->assetUrl = $asset->url();

        $this->template = route('glide-image.preset', [
            'width' => '__width__',
            'height' => '__height__',
            's' => '__signature__',
            'format' => '__format__',
            'file' => ltrim($this->assetUrl, '/'),
        ]);
    }

    public function generate(int $width, ?int $height, string $format): string
    {
        $params = $this->signature->addSignature($this->assetUrl, ['width' => $width, 'height' => $height, 'format' => '.'.$format]);

        return strtr($this->template, [
            '__width__' => $params['width'],
            '__height__' => $params['height'],
            '__signature__' => $params['s'],
            '__format__' => $params['format'],
        ]);
    }
}
