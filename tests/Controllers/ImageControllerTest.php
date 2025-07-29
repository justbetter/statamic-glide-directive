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

    #[Test]
    public function it_returns_404_for_invalid_signature(): void
    {
        $asset = $this->uploadTestAsset('upload.png');

        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);

        $this->controller->getImageByPreset(
            request(),
            'xs',
            'contain',
            'invalid-signature',
            $asset->filename(),
            '.webp'
        );
    }

    #[Test]
    public function it_handles_different_format_extensions(): void
    {
        $asset = $this->uploadTestAsset('upload.png');
        $formats = ['.webp', '.jpg', '.png', '.gif'];

        foreach ($formats as $format) {
            $exceptionThrown = false;

            try {
                $this->controller->getImageByPreset(
                    request(),
                    'md',
                    'crop',
                    'invalid-signature',
                    $asset->filename(),
                    $format
                );
            } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
                $exceptionThrown = true;
            }

            $this->assertTrue($exceptionThrown, "Expected NotFoundHttpException for format: {$format}");
        }
    }

    #[Test]
    public function it_successfully_validates_signature_and_builds_image(): void
    {
        $asset = $this->uploadTestAsset('upload.png');
        $signatureFactory = new \League\Glide\Signatures\Signature(config('app.key'));

        $params = [
            's' => '',
            'preset' => 'xs',
            'fit' => 'contain',
            'format' => '.webp',
        ];

        $signature = $signatureFactory->generateSignature($asset->url(), $params);
        $params['s'] = $signature;

        $cachePath = config('statamic.assets.image_manipulation.cache_path');
        $storagePrefix = config('justbetter.glide-directive.storage_prefix');
        $expectedImagePath = $cachePath.'/'.$storagePrefix.'/xs/contain/'.$signature.$asset->url().'.webp';

        $directory = dirname($expectedImagePath);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($expectedImagePath, 'fake-image-content');

        try {
            $response = $this->controller->getImageByPreset(
                request(),
                'xs',
                'contain',
                $signature,
                $asset->url(),
                '.webp'
            );

            $this->assertInstanceOf(\Symfony\Component\HttpFoundation\BinaryFileResponse::class, $response);
            $this->assertEquals(200, $response->getStatusCode());

            $this->assertEquals('max-age=31536000, public', $response->headers->get('Cache-Control'));
            $this->assertEquals($asset->mimeType(), $response->headers->get('Content-Type'));

        } finally {
            if (file_exists($expectedImagePath)) {
                unlink($expectedImagePath);
            }

            $dir = dirname($expectedImagePath);
            while ($dir && $dir !== $cachePath && is_dir($dir) && scandir($dir) && count(scandir($dir)) === 2) {
                rmdir($dir);
                $dir = dirname($dir);
            }
        }
    }

    #[Test]
    public function it_returns_404_when_image_file_missing_after_valid_signature(): void
    {
        $asset = $this->uploadTestAsset('upload.png');

        $signatureFactory = new \League\Glide\Signatures\Signature(config('app.key'));
        $params = [
            's' => '',
            'preset' => 'md',
            'fit' => 'crop',
            'format' => '.jpg',
        ];

        $signature = $signatureFactory->generateSignature($asset->url(), $params);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);

        $this->controller->getImageByPreset(
            request(),
            'md',
            'crop',
            $signature,
            $asset->filename(),
            '.jpg'
        );
    }

    #[Test]
    public function it_handles_different_preset_values(): void
    {
        $asset = $this->uploadTestAsset('upload.png');

        $presets = ['xs', 'sm', 'md', 'lg', 'xl', '2xl'];

        foreach ($presets as $preset) {
            $exceptionThrown = false;

            try {
                $this->controller->getImageByPreset(
                    request(),
                    $preset,
                    'contain',
                    'invalid-signature',
                    $asset->filename(),
                    '.webp'
                );
            } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
                $exceptionThrown = true;
            }

            $this->assertTrue($exceptionThrown, "Expected NotFoundHttpException for preset: {$preset}");
        }
    }

    #[Test]
    public function it_handles_different_fit_values(): void
    {
        $asset = $this->uploadTestAsset('upload.png');

        $fitModes = ['contain', 'crop', 'fill', 'stretch'];

        foreach ($fitModes as $fit) {
            $exceptionThrown = false;

            try {
                $this->controller->getImageByPreset(
                    request(),
                    'md',
                    $fit,
                    'invalid-signature',
                    $asset->filename(),
                    '.webp'
                );
            } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
                $exceptionThrown = true;
            }

            $this->assertTrue($exceptionThrown, "Expected NotFoundHttpException for fit: {$fit}");
        }
    }
}
