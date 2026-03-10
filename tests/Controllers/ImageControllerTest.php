<?php

namespace JustBetter\GlideDirective\Controllers\Tests;

use JustBetter\GlideDirective\Controllers\ImageController;
use JustBetter\GlideDirective\Tests\TestCase;
use League\Glide\Server;
use League\Glide\Signatures\Signature;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ImageControllerTest extends TestCase
{
    protected ImageController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = app(ImageController::class);
    }

    #[Test]
    public function it_returns_404_for_missing_asset(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $this->controller->getImageByPreset(
            request(),
            350,
            500,
            'dummy-signature',
            'non-existent-file.jpg',
            'jpg'
        );
    }

    #[Test]
    public function it_returns_404_for_invalid_signature(): void
    {
        $asset = $this->uploadTestAsset('upload.png');

        $this->expectException(NotFoundHttpException::class);

        $this->controller->getImageByPreset(
            request(),
            350,
            500,
            'invalid-signature',
            ltrim($asset->url(), '/'),
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
                    350,
                    500,
                    'invalid-signature',
                    ltrim($asset->url(), '/'),
                    $format
                );
            } catch (NotFoundHttpException $e) {
                $exceptionThrown = true;
            }

            $this->assertTrue($exceptionThrown, "Expected NotFoundHttpException for format: {$format}");
        }
    }

    #[Test]
    public function it_successfully_validates_signature_and_builds_image(): void
    {
        $asset = $this->uploadTestAsset('upload.png');
        $signatureFactory = new Signature(config('app.key'));

        $params = [
            's' => '',
            'width' => 350,
            'height' => 500,
            'format' => '.webp',
        ];

        $signature = $signatureFactory->generateSignature($asset->url(), $params);
        $params['s'] = $signature;

        $cachePath = config('justbetter.glide-directive.cache_prefix');
        $storagePrefix = config('justbetter.glide-directive.storage_prefix');
        $expectedImagePath = public_path($cachePath.'/'.$storagePrefix.'/350/500/'.$signature.$asset->url().'.webp');

        $directory = dirname($expectedImagePath);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($expectedImagePath, 'fake-image-content');

        try {
            $response = $this->controller->getImageByPreset(
                request(),
                350,
                500,
                $signature,
                ltrim($asset->url(), '/'),
                '.webp'
            );

            $this->assertInstanceOf(BinaryFileResponse::class, $response);
            $this->assertEquals(200, $response->getStatusCode());

            /** @var string $cacheControl */
            $cacheControl = $response->headers->get('Cache-Control');
            $this->assertStringContainsString('public', $cacheControl);
            $this->assertStringContainsString('max-age=31536000', $cacheControl);
            $this->assertStringContainsString('immutable', $cacheControl);
            $this->assertEquals('image/webp', $response->headers->get('Content-Type'));

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

        $signatureFactory = new Signature(config('app.key'));
        $params = [
            's' => '',
            'width' => 350,
            'height' => 500,
            'format' => '.jpg',
        ];

        $signature = $signatureFactory->generateSignature($asset->url(), $params);

        $storagePrefix = config('justbetter.glide-directive.storage_prefix');
        $imagePath = $storagePrefix.'/350/500/'.$signature.$asset->url().'.jpg';

        /** @var Server $server */
        $server = $this->mock(Server::class, function (MockInterface $mock) use ($asset, $imagePath) {
            $mock->shouldReceive('setSource')->andReturnSelf();
            $mock->shouldReceive('setSourcePathPrefix')->andReturnSelf();
            $mock->shouldReceive('setCache')->andReturnSelf();
            $mock->shouldReceive('setCachePathPrefix')->andReturnSelf();
            $mock->shouldReceive('setCachePathCallable')->andReturnSelf();

            $mock->shouldReceive('makeImage')
                ->with($asset->url(), ['w' => 350, 'fm' => 'jpg', 'h' => 500, 'q' => 85, 'fit' => 'crop-focal'])
                ->andReturn($imagePath);
        });

        $controller = new ImageController(
            $server
        );

        $this->expectException(NotFoundHttpException::class);

        $controller->getImageByPreset(
            request(),
            350,
            500,
            $signature,
            ltrim($asset->url(), '/'),
            '.jpg'
        );
    }

    #[Test]
    public function it_handles_different_preset_values(): void
    {
        $asset = $this->uploadTestAsset('upload.png');

        $presets = [['w' => 350, 'h' => 500], ['w' => 500, 'h' => 750], ['w' => 500, 'h' => 1500]];

        foreach ($presets as $preset) {
            $exceptionThrown = false;

            try {
                $this->controller->getImageByPreset(
                    request(),
                    $preset['w'],
                    $preset['h'],
                    'invalid-signature',
                    ltrim($asset->url(), '/'),
                    '.webp'
                );
            } catch (NotFoundHttpException $e) {
                $exceptionThrown = true;
            }

            $this->assertTrue($exceptionThrown, "Expected NotFoundHttpException for preset: {$preset['w']}x{$preset['h']}");
        }
    }

    #[Test]
    public function it_handles_null_asset_in_build_image(): void
    {
        $controller = new ImageController(
            app(Server::class)
        );

        $reflection = new ReflectionClass($controller);
        $assetProperty = $reflection->getProperty('asset');
        $assetProperty->setAccessible(true);
        $assetProperty->setValue($controller, null);

        $paramsProperty = $reflection->getProperty('params');
        $paramsProperty->setAccessible(true);
        $paramsProperty->setValue($controller, ['p' => 'xs', 'fit' => 'contain', 's' => 'sig', 'format' => '.webp']);

        $method = $reflection->getMethod('buildImage');
        $method->setAccessible(true);

        $result = $method->invoke($controller);

        $this->assertNull($result);
    }
}
