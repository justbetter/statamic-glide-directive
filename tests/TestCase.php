<?php

namespace JustBetter\GlideDirective\Tests;

use Illuminate\Foundation\Testing\Concerns\InteractsWithViews;
use Illuminate\Http\UploadedFile;
use JustBetter\GlideDirective\ServiceProvider;
use Statamic\Assets\Asset;
use Statamic\Assets\AssetContainer;
use Statamic\Testing\AddonTestCase;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

abstract class TestCase extends AddonTestCase
{
    use InteractsWithViews;
    use PreventsSavingStacheItemsToDisk;

    protected function resolveApplicationConfiguration($app): void
    {
        parent::resolveApplicationConfiguration($app);

        $app['config']->set('app.key', 'base64:statamic-glide-directive-test-key');
        $app['config']->set('statamic.assets.image_manipulation.driver', 'gd');
        $app['config']->set('statamic.assets.image_manipulation.cache', true);
        $app['config']->set('statamic.assets.image_manipulation.cache_path', __DIR__.'/Assets/img');

        $app['config']->set('filesystems.disks.assets', [
            'driver' => 'local',
            'root' => $this->assetPath(),
            'url' => '/test',
        ]);
    }

    protected string $addonServiceProvider = ServiceProvider::class;

    protected mixed $blade;

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

    protected function uploadTestAsset(string $filename): Asset
    {
        UploadedFile::fake();

        /* @phpstan-ignore-next-line */
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
