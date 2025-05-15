<?php

namespace JustBetter\GlideDirective\Controllers\Tests;

use JustBetter\GlideDirective\Controllers\ImageController;
use JustBetter\GlideDirective\Responsive;
use JustBetter\GlideDirective\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ImageControllerTest extends TestCase
{
    protected ImageController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = app(ImageController::class);
    }

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

    #[Test]
    public function it_returns_404_for_missing_asset(): void
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);

        $this->controller->getImageByPreset(
            request(),
            'xs',
            'contain',
            'dummy-signature',
            'non-existent-file.jpg',
            'jpg'
        );
    }
}
