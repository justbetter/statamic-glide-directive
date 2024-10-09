<?php

namespace JustBetter\GlideDirective\Tests\View\Blade;

use Statamic\Assets\AssetContainer;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Test;
use JustBetter\GlideDirective\Tests\TestCase;
use JustBetter\GlideDirective\Responsive;

class ResponsiveDirectiveTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function test_responsive_directive_tag()
    {
        $blade = "@responsive('test.png')";
        $expected = "<?php echo \JustBetter\GlideDirective\Responsive::handle('test.png'); ?>";

        $this->assertSame($expected, $this->blade->compileString($blade));
    }

    #[Test]
    public function test_responsive_directive_tag_handle()
    {
        config(['filesystems.disks.assets' => [
            'driver' => 'local',
            'root' => $this->assetPath(),
            'url' => '/test',
        ]]);

        $assetContainer = (new AssetContainer)
            ->handle('test_container')
            ->disk('assets')
            ->save();

        copy($this->assetPath('test.png'), $this->assetPath('upload.png'));

        $file = new UploadedFile($this->assetPath('upload.png'), 'test.png');
        $path = $file->getClientOriginalName();
        $asset = $assetContainer->makeAsset($path)->upload($file);
        $view = Responsive::handle($asset);

        $asset->delete();

        $this->assertStringContainsString('<picture', $view->render());
    }
}