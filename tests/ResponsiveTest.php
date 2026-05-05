<?php

namespace JustBetter\GlideDirective\Tests;

use JustBetter\GlideDirective\Responsive;
use PHPUnit\Framework\Attributes\Test;
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
    public function it_can_focus_to_position(): void
    {
        $focuspoint = Responsive::focusToPosition('50-50');

        $this->assertSame('50% 50%', $focuspoint);
    }

    #[Test]
    public function it_returns_focus_to_position_when_not_provided(): void
    {
        $focuspoint = Responsive::focusToPosition('50 50');

        $this->assertSame('50 50', $focuspoint);
    }

    #[Test]
    public function it_renders_object_position_style_when_asset_has_focus(): void
    {
        $asset = $this->uploadTestAsset('upload.png');

        $asset->set('focus', '25-75');
        $asset->save();

        $view = Responsive::handle($asset, [
            'cover' => true,
        ]);

        /* @phpstan-ignore-next-line */
        $rendered = $view->render();

        $this->assertStringContainsString('style="object-position: 25% 75%"', $rendered);

        $asset->delete();
    }

    protected function extractSrcsetWidths(string $srcset): array
    {
        preg_match_all('/\s(\d+)w/', $srcset, $matches);

        // @phpstan-ignore-next-line
        return array_map('intval', $matches[1] ?? []);
    }
}
