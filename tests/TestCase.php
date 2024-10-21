<?php

namespace JustBetter\GlideDirective\Tests;

use Illuminate\Http\UploadedFile;
use Statamic\Assets\AssetContainer;
use Statamic\Testing\AddonTestCase;
use JustBetter\GlideDirective\ServiceProvider;
use Illuminate\Foundation\Testing\Concerns\InteractsWithViews;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

abstract class TestCase extends AddonTestCase
{
    use InteractsWithViews;
    use PreventsSavingStacheItemsToDisk;

    protected function resolveApplicationConfiguration($app): void
    {
        parent::resolveApplicationConfiguration($app);

        $app['config']->set('statamic.assets.image_manipulation.driver', 'gd');
        $app['config']->set('statamic.assets.image_manipulation.cache', true);
        $app['config']->set('statamic.assets.image_manipulation.cache_path', __DIR__ . '/Assets/img');

        $app['config']->set('filesystems.disks.assets', [
            'driver' => 'local',
            'root' => $this->assetPath(),
            'url' => '/test',
        ]);
    }

    protected string $addonServiceProvider = ServiceProvider::class;

    protected $blade;

    protected function setUp(): void
    {
        parent::setUp();

        $this->blade = app('blade.compiler');
    }

    public function assetPath(string $file = ''): string
    {
        $path = __DIR__.'/Assets';

        if (strlen($file) > 0) {
            $path .= '/'.$file;
        }

        return $path;
    }

    protected function assertDirectiveOutput($expected, $expression, $variables = [], $message = '')
    {
        $compiled = $this->blade->compileString($expression);

        ob_start();
        extract($variables);
        eval(' ?>'.$compiled.'<?php ');

        $output = ob_get_clean();

        $this->assertSame($expected, $output, $message);
    }

    protected function uploadTestAsset(string $filename)
    {
        $assetContainer = (new AssetContainer)
            ->handle('test_container')
            ->disk('assets')
            ->save();

        copy($this->assetPath('test.png'), $this->assetPath($filename));

        $file = new UploadedFile($this->assetPath($filename), 'test.png');
        $path = $file->getClientOriginalName();

        return $assetContainer->makeAsset($path)->upload($file);
    }
}
