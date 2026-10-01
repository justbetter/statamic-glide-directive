<?php

namespace JustBetter\GlideDirective\Tests;

use JustBetter\GlideDirective\GlideUrlGenerator;
use JustBetter\GlideDirective\Responsive;
use League\Glide\Signatures\SignatureFactory;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Assets\AssetContainer;

class GlideUrlGeneratorTest extends TestCase
{
    #[Test]
    public function it_generates_the_same_url_as_the_route(): void
    {
        $asset = $this->uploadTestAsset('upload.png');

        $params = SignatureFactory::create(config('app.key'))
            ->addSignature($asset->url(), ['width' => 640, 'height' => 480, 'format' => '.webp']);

        $expected = route('glide-image.preset', array_merge($params, [
            'file' => ltrim($asset->url(), '/'),
        ]));

        $this->assertSame($expected, (new GlideUrlGenerator($asset))->generate(640, 480, 'webp'));

        $asset->delete();
    }

    #[Test]
    public function it_generates_a_glide_url_through_responsive(): void
    {
        $asset = $this->uploadTestAsset('upload.png');

        $this->assertSame(
            (new GlideUrlGenerator($asset))->generate(320, 240, 'avif'),
            Responsive::getGlideUrl($asset, 320, 240, 'avif')
        );

        $asset->delete();
    }

    #[Test]
    public function it_does_not_build_srcsets_for_svg_assets(): void
    {
        $asset = (new AssetContainer)
            ->handle('test_container')
            /* @phpstan-ignore-next-line */
            ->disk('assets')
            ->save()
            ->makeAsset('logo.svg');

        $view = Responsive::handle($asset);

        /* @phpstan-ignore-next-line */
        $this->assertSame([], $view->getData()['srcsets']);
    }
}
