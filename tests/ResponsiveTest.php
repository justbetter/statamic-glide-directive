<?php

namespace JustBetter\GlideDirective\Tests;

use Illuminate\Support\Facades\Queue;
use JustBetter\GlideDirective\Jobs\GenerateGlideImageJob;
use JustBetter\GlideDirective\Responsive;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Glide;
use Statamic\Fields\Value;
use Statamic\Statamic;

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
    public function it_handles_image_generation(): void
    {
        Queue::fake();
        $asset = $this->uploadTestAsset('upload.png');

        // Test uncached image
        /* @phpstan-ignore-next-line */
        Glide::cacheStore()->flush();
        $view = Responsive::handle($asset);
        /* @phpstan-ignore-next-line */
        $view->render();
        Queue::assertPushed(GenerateGlideImageJob::class);

        // Test cached image
        Statamic::tag('glide')->params([
            'src' => $asset->url(),
            'preset' => 'xs',
        ])->fetch();

        $view = Responsive::handle($asset);
        /* @phpstan-ignore-next-line */
        $rendered = $view->render();

        $this->assertStringContainsString('src="', $rendered);
        $this->assertStringContainsString('.png', $rendered);

        $asset->delete();
    }

    #[Test]
    public function it_respects_focal_point(): void
    {
        $asset = $this->uploadTestAsset('upload.png');
        $asset->data(['focus' => '50-50'])->save();

        $view = Responsive::handle($asset);
        /* @phpstan-ignore-next-line */
        $rendered = $view->render();

        $this->assertStringContainsString('crop-50-50', $rendered);

        $asset->delete();
    }

    #[Test]
    public function it_handles_different_source_configurations(): void
    {
        $asset = $this->uploadTestAsset('upload.png');

        // Test webp source
        config()->set('justbetter.glide-directive.sources', 'webp');
        $view = Responsive::handle($asset);
        /* @phpstan-ignore-next-line */
        $rendered = $view->render();
        $this->assertStringContainsString('<source type="image/webp"', $rendered);

        // Test mime_type source
        config()->set('justbetter.glide-directive.sources', 'mime_type');
        $view = Responsive::handle($asset);
        /* @phpstan-ignore-next-line */
        $rendered = $view->render();
        $this->assertStringContainsString('<source type="'.$asset->mimeType().'"', $rendered);

        // Test both sources
        config()->set('justbetter.glide-directive.sources', 'both');
        $view = Responsive::handle($asset);
        /* @phpstan-ignore-next-line */
        $rendered = $view->render();
        $this->assertStringContainsString('<source type="image/webp"', $rendered);
        $this->assertStringContainsString('<source type="'.$asset->mimeType().'"', $rendered);

        $asset->delete();
    }
}
