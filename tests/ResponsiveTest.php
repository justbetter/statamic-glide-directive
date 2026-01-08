<?php

namespace JustBetter\GlideDirective\Tests;

use JustBetter\GlideDirective\Responsive;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Statamic\Fields\Value;

class ResponsiveTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('justbetter.glide-directive.sources', 'webp');
        config()->set('justbetter.glide-directive.placeholder', true);
        config()->set('statamic.assets.image_manipulation.presets', [
            'placeholder' => ['w' => 32, 'h' => 32, 'q' => 100, 'fit' => 'contain'],
            'xs' => ['w' => 320, 'h' => 320, 'q' => 100, 'fit' => 'contain'],
            'sm' => ['w' => 640, 'h' => 640, 'q' => 100, 'fit' => 'contain'],
            'sm-h' => ['w' => null, 'h' => 640, 'q' => 100, 'fit' => 'contain'],
        ]);
    }

    #[Test]
    public function it_handles_null_and_invalid_assets(): void
    {
        $this->assertSame('', Responsive::handle(null));
        $this->assertSame('', Responsive::handle('not-an-asset'));
    }

    #[Test]
    public function it_handles_asset_value_object(): void
    {
        $asset = $this->uploadTestAsset('upload.png');
        $value = new Value($asset);

        $view = Responsive::handle($value);
        /* @phpstan-ignore-next-line */
        $rendered = $view->render();

        $this->assertStringContainsString('<picture>', $rendered);
        $this->assertStringContainsString('src="', $rendered);

        $asset->delete();
    }

    #[Test]
    public function it_handles_custom_attributes(): void
    {
        $asset = $this->uploadTestAsset('upload.png');

        $view = Responsive::handle($asset, [
            'class' => 'custom-class',
            'alt' => 'Custom Alt',
            'loading' => 'lazy',
            'width' => 100,
            'height' => 100,
            'data-custom' => 'value',
        ]);

        /* @phpstan-ignore-next-line */
        $rendered = $view->render();

        $this->assertStringContainsString('class="custom-class"', $rendered);
        $this->assertStringContainsString('alt="Custom Alt"', $rendered);
        $this->assertStringContainsString('loading="lazy"', $rendered);
        $this->assertStringContainsString('width="100"', $rendered);
        $this->assertStringContainsString('height="100"', $rendered);
        $this->assertStringContainsString('data-custom="value"', $rendered);

        $asset->delete();
    }

    #[Test]
    public function it_creates_mime_type_source_when_configured(): void
    {
        $asset = $this->uploadTestAsset('upload.png');
        config()->set('justbetter.glide-directive.sources', 'mime_type');

        $view = Responsive::handle($asset);
        /* @phpstan-ignore-next-line */
        $rendered = $view->render();

        $this->assertStringNotContainsString('<source type="image/webp"', $rendered);

        config()->set('justbetter.glide-directive.sources', 'webp');

        $view = Responsive::handle($asset);
        /* @phpstan-ignore-next-line */
        $rendered = $view->render();

        $this->assertStringNotContainsString('<source type="image/png"', $rendered);

        $asset->delete();
    }

    #[Test]
    public function it_returns_empty_presets_for_small_images(): void
    {
        $asset = $this->uploadTestAsset('upload.png');
        $assetWidth = $asset->width();
        config()->set('justbetter.glide-directive.image_resize_threshold', $assetWidth + 1);

        $presets = Responsive::getPresets($asset);

        $this->assertEmpty($presets);

        $asset->delete();
    }

    #[Test]
    public function it_removes_placeholder_when_disabled(): void
    {
        $asset = $this->uploadTestAsset('upload.png');
        config()->set('justbetter.glide-directive.placeholder', false);
        config()->set('statamic.assets.image_manipulation.presets', [
            'placeholder' => ['w' => 32, 'h' => 32, 'q' => 100, 'fit' => 'contain'],
            'xs' => ['w' => 320, 'h' => 320, 'q' => 100, 'fit' => 'contain'],
        ]);

        $presets = Responsive::getPresets($asset);

        $this->assertArrayHasKey('webp', $presets);
        $this->assertStringNotContainsString('placeholder', $presets['webp'] ?? '');

        $asset->delete();
    }

    #[Test]
    public function it_skips_presets_without_width(): void
    {
        $asset = $this->uploadTestAsset('upload.png');
        
        $assetWidth = $asset->width();
        $assetHeight = $asset->height();
        $isVertical = $assetHeight > $assetWidth;
        
        config()->set('statamic.assets.image_manipulation.presets', [
            'xs' => ['w' => $isVertical ? 640 : 320, 'h' => $isVertical ? 320 : 640, 'q' => 100, 'fit' => 'contain'],
            'sm-h' => ['h' => 640, 'q' => 100, 'fit' => 'contain'],
        ]);

        $presets = Responsive::getPresets($asset);

        $this->assertArrayHasKey('webp', $presets);
        $this->assertStringNotContainsString('sm-h', $presets['webp'] ?? '');

        $asset->delete();
    }

    #[Test]
    public function it_uses_asset_url_when_no_sources_found(): void
    {
        $asset = $this->uploadTestAsset('upload.png');
        config()->set('justbetter.glide-directive.sources', 'both');
        config()->set('statamic.assets.image_manipulation.presets', [
            'placeholder' => ['w' => 32, 'h' => 32, 'q' => 100, 'fit' => 'contain'],
        ]);

        $presets = Responsive::getPresets($asset);

        $this->assertArrayHasKey('placeholder', $presets);
        $this->assertEquals($asset->url(), $presets['placeholder']);

        $asset->delete();
    }

    #[Test]
    public function it_creates_placeholder_when_missing(): void
    {
        $asset = $this->uploadTestAsset('upload.png');
        config()->set('justbetter.glide-directive.placeholder', true);
        config()->set('statamic.assets.image_manipulation.presets', [
            'xs' => ['w' => 320, 'h' => 320, 'q' => 100, 'fit' => 'contain'],
        ]);

        $presets = Responsive::getPresets($asset);

        $this->assertArrayHasKey('placeholder', $presets);
        $this->assertNotNull($presets['placeholder']);

        $asset->delete();
    }

    #[Test]
    public function it_returns_asset_url_when_default_preset_not_found(): void
    {
        $asset = $this->uploadTestAsset('upload.png');
        config()->set('justbetter.glide-directive.default_preset', 'nonexistent');
        config()->set('statamic.assets.image_manipulation.presets', [
            'xs' => ['w' => 320, 'h' => 320, 'q' => 100, 'fit' => 'contain'],
        ]);

        $reflection = new ReflectionClass(Responsive::class);
        $method = $reflection->getMethod('getDefaultPreset');
        $method->setAccessible(true);

        $result = $method->invoke(null, $asset);

        $this->assertEquals($asset->url(), $result);

        $asset->delete();
    }

    #[Test]
    public function it_creates_image_manipulator(): void
    {
        $asset = $this->uploadTestAsset('upload.png');

        $reflection = new ReflectionClass(Responsive::class);
        $method = $reflection->getMethod('getManipulator');
        $method->setAccessible(true);

        $result = $method->invoke(null, $asset, 'xs', 'contain', 'webp');

        $this->assertNotNull($result);

        $asset->delete();
    }
}
