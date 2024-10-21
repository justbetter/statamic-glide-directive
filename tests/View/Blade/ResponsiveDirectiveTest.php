<?php

namespace JustBetter\GlideDirective\Tests\View\Blade;

use Illuminate\Support\Facades\Queue;
use JustBetter\GlideDirective\Jobs\GenerateGlideImageJob;
use Statamic\Assets\AssetContainer;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Test;
use JustBetter\GlideDirective\Tests\TestCase;
use JustBetter\GlideDirective\Responsive;
use Statamic\Statamic;
use Illuminate\Foundation\Bus\PendingDispatch;

class ResponsiveDirectiveTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function test_responsive_directive_tag(): void
    {
        $blade = "@responsive('test.png')";
        $expected = "<?php echo \JustBetter\GlideDirective\Responsive::handle('test.png'); ?>";

        $this->assertSame($expected, $this->blade->compileString($blade));
    }

    #[Test]
    public function test_responsive_directive_tag_handle(): void
    {
        $asset = $this->uploadTestAsset('upload.png');
        $view = Responsive::handle($asset);
        $asset->delete();

        /* @phpstan-ignore-next-line */
        $this->assertStringContainsString('<picture', $view->render());
    }

    #[Test]
    public function test_responsive_directive_tag_cant_handle_string(): void
    {
        $asset = $this->uploadTestAsset('upload.png');
        $view = Responsive::handle($asset->url());
        $asset->delete();

        $this->assertSame($view, "");
    }

    #[Test]
    public function can_dispatch_glide_job(): void
    {
        Queue::fake();
        Queue::assertNothingPushed();

        $asset = $this->uploadTestAsset('upload.png');
        GenerateGlideImageJob::dispatch($asset, 'xs', '', null);

        Queue::assertPushed(GenerateGlideImageJob::class, 1);
        $asset->delete();
    }

    #[Test]
    public function can_generate_glide_preset(): void
    {
        $asset = $this->uploadTestAsset('upload.png');

        $glideImage = Statamic::tag('glide')->params(
            [
                'preset' => 'xs',
                'src' => $asset->url(),
                'format' => null,
                'fit' => null
            ]
        )->fetch();

        $asset->delete();

        $this->assertStringContainsString('/containers/test_container/test', $glideImage);
    }

    #[Test]
    public function can_get_presets_for_asset(): void
    {
        $asset = $this->uploadTestAsset('upload.png');
        $presets = Responsive::getPresets($asset);

        $this->assertIsArray($presets);
        $this->assertArrayHasKey('placeholder', $presets);
    }
}