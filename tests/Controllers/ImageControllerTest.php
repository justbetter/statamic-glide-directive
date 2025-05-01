<?php

namespace JustBetter\GlideDirective\Controllers\Tests;

use JustBetter\GlideDirective\Responsive;
use JustBetter\GlideDirective\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ImageControllerTest extends TestCase
{
    #[Test]
    public function it_returns_placeholder(): void
    {
        $asset = $this->uploadTestAsset('upload.png');
        $response = $this->get('/glide-image/placeholder/'.$asset->url());

        $response->assertSuccessful();
    }

    #[Test]
    public function it_gets_presets(): void
    {
        $asset = $this->uploadTestAsset('upload.png');
        $presets = Responsive::getPresets($asset);

        $this->assertArrayHasKey('webp', $presets);
    }
}
