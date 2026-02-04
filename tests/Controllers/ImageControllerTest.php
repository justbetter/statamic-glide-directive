<?php

namespace JustBetter\GlideDirective\Controllers\Tests;

use JustBetter\GlideDirective\Controllers\ImageController;
use JustBetter\GlideDirective\Responsive;
use JustBetter\GlideDirective\Tests\TestCase;
use League\Glide\Server;
use League\Glide\Signatures\Signature;
use League\Glide\Signatures\SignatureFactory;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Statamic\Imaging\ImageGenerator;
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
    public function it_gets_presets(): void
    {
        $asset = $this->uploadTestAsset('upload.png');
        $presets = Responsive::getPresets($asset);

        $this->assertArrayHasKey('webp', $presets);
    }

    #[Test]
    public function it_returns_404_for_missing_asset(): void
    {
        $this->expectException(NotFoundHttpException::class);

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

        $this->expectException(NotFoundHttpException::class);

        $this->controller->getImageByPreset(
            request(),
            'xs',
            'contain',
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
                    'md',
                    'crop',
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
            'p' => 'xs',
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
            'p' => 'md',
            'fit' => 'crop',
            'format' => '.jpg',
        ];

        $signature = $signatureFactory->generateSignature($asset->url(), $params);

        $storagePrefix = config('justbetter.glide-directive.storage_prefix');
        $imagePath = $storagePrefix.'/md/crop/'.$signature.$asset->url().'.jpg';

        /** @var Server $server */
        $server = $this->mock(Server::class, function (MockInterface $mock) use ($asset, $signature, $imagePath) {
            $mock->shouldReceive('setSource')->andReturnSelf();
            $mock->shouldReceive('setSourcePathPrefix')->andReturnSelf();
            $mock->shouldReceive('setCachePathPrefix')->andReturnSelf();
            $mock->shouldReceive('setCachePathCallable')->andReturnSelf();
            $mock->shouldReceive('makeImage')
                ->with($asset->url(), ['s' => $signature, 'p' => 'md', 'fit' => 'crop', 'format' => '.jpg'])
                ->andReturn($imagePath);
        });

        $controller = new ImageController(
            app(ImageGenerator::class),
            $server
        );

        $this->expectException(NotFoundHttpException::class);

        $controller->getImageByPreset(
            request(),
            'md',
            'crop',
            $signature,
            ltrim($asset->url(), '/'),
            '.jpg'
        );
    }

    #[Test]
    public function it_applies_lossless_false_for_png_to_webp_when_not_overridden(): void
    {
        $asset = $this->uploadTestAsset('upload.png');
        config()->set('statamic.assets.image_manipulation.presets.md', [
            'w' => 640,
            'h' => 640,
            'q' => 80,
            'fit' => 'contain',
        ]);

        $signatureFactory = new Signature(config('app.key'));
        $params = [
            's' => '',
            'p' => 'md',
            'fit' => 'crop',
            'format' => '.webp',
        ];

        $signature = $signatureFactory->generateSignature($asset->url(), $params);

        $storagePrefix = config('justbetter.glide-directive.storage_prefix');
        $imagePath = $storagePrefix.'/md/crop/'.$signature.$asset->url().'.webp';

        /** @var Server $server */
        $server = $this->mock(Server::class, function (MockInterface $mock) use ($asset, $signature, $imagePath) {
            $mock->shouldReceive('setSource')->andReturnSelf();
            $mock->shouldReceive('setSourcePathPrefix')->andReturnSelf();
            $mock->shouldReceive('setCachePathPrefix')->andReturnSelf();
            $mock->shouldReceive('setCachePathCallable')->andReturnSelf();
            $mock->shouldReceive('makeImage')
                ->with($asset->url(), Mockery::on(function ($params) use ($signature) {
                    return ($params['s'] ?? null) === $signature
                        && ($params['p'] ?? null) === 'md'
                        && ($params['fit'] ?? null) === 'crop'
                        && ($params['format'] ?? null) === '.webp'
                        && array_key_exists('lossless', $params)
                        && (int) $params['lossless'] === 0;
                }))
                ->andReturn($imagePath);
        });

        $controller = new ImageController(
            app(ImageGenerator::class),
            $server
        );

        $this->expectException(NotFoundHttpException::class);

        $controller->getImageByPreset(
            request(),
            'md',
            'crop',
            $signature,
            ltrim($asset->url(), '/'),
            '.webp'
        );
    }

    #[Test]
    public function it_respects_lossless_preset_override(): void
    {
        $asset = $this->uploadTestAsset('upload.png');
        config()->set('statamic.assets.image_manipulation.presets.md', [
            'w' => 640,
            'h' => 640,
            'q' => 80,
            'fit' => 'contain',
            'lossless' => true,
        ]);

        $signatureFactory = new Signature(config('app.key'));
        $params = [
            's' => '',
            'p' => 'md',
            'fit' => 'contain',
            'format' => '.webp',
        ];

        $signature = $signatureFactory->generateSignature($asset->url(), $params);

        $storagePrefix = config('justbetter.glide-directive.storage_prefix');
        $imagePath = $storagePrefix.'/md/contain/'.$signature.$asset->url().'.webp';

        /** @var Server $server */
        $server = $this->mock(Server::class, function (MockInterface $mock) use ($asset, $signature, $imagePath) {
            $mock->shouldReceive('setSource')->andReturnSelf();
            $mock->shouldReceive('setSourcePathPrefix')->andReturnSelf();
            $mock->shouldReceive('setCachePathPrefix')->andReturnSelf();
            $mock->shouldReceive('setCachePathCallable')->andReturnSelf();
            $mock->shouldReceive('makeImage')
                ->with($asset->url(), Mockery::on(function ($params) use ($signature) {
                    return ($params['s'] ?? null) === $signature
                        && ($params['p'] ?? null) === 'md'
                        && ($params['fit'] ?? null) === 'contain'
                        && ($params['format'] ?? null) === '.webp'
                        && ! array_key_exists('lossless', $params);
                }))
                ->andReturn($imagePath);
        });

        $controller = new ImageController(
            app(ImageGenerator::class),
            $server
        );

        $this->expectException(NotFoundHttpException::class);

        $controller->getImageByPreset(
            request(),
            'md',
            'contain',
            $signature,
            ltrim($asset->url(), '/'),
            '.webp'
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
                    ltrim($asset->url(), '/'),
                    '.webp'
                );
            } catch (NotFoundHttpException $e) {
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
                    ltrim($asset->url(), '/'),
                    '.webp'
                );
            } catch (NotFoundHttpException $e) {
                $exceptionThrown = true;
            }

            $this->assertTrue($exceptionThrown, "Expected NotFoundHttpException for fit: {$fit}");
        }
    }

    #[Test]
    public function it_handles_null_asset_in_build_image(): void
    {
        $controller = new ImageController(
            app(ImageGenerator::class),
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

    #[Test]
    public function it_handles_null_asset_in_get_cache_path_callable(): void
    {
        $controller = new ImageController(
            app(ImageGenerator::class),
            app(Server::class)
        );

        $reflection = new ReflectionClass($controller);
        $assetProperty = $reflection->getProperty('asset');
        $assetProperty->setAccessible(true);
        $assetProperty->setValue($controller, null);

        $paramsProperty = $reflection->getProperty('params');
        $paramsProperty->setAccessible(true);
        $paramsProperty->setValue($controller, ['p' => 'xs', 'fit' => 'contain', 's' => 'sig', 'format' => '.webp']);

        $method = $reflection->getMethod('getCachePathCallable');
        $method->setAccessible(true);

        $result = $method->invoke($controller);

        $this->assertNull($result);
    }

    #[Test]
    public function it_can_know_the_right_mime_type_for_jpg(): void
    {
        $asset = $this->uploadTestAsset('upload.jpg');

        $params = [
            's' => '',
            'p' => 'xs',
            'fit' => 'contain',
            'format' => '.jpg',
        ];

        $signatureFactory = SignatureFactory::create(config('app.key'));
        $signed = $signatureFactory->addSignature($asset->url(), $params);
        $signature = $signed['s'];

        $cachePath = config('statamic.assets.image_manipulation.cache_path');
        $storagePrefix = config('justbetter.glide-directive.storage_prefix');
        $expectedImagePath = $cachePath.'/'.$storagePrefix.'/xs/contain/'.$signature.$asset->url().'.jpg';

        $directory = dirname($expectedImagePath);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($expectedImagePath, 'fake-image-content');

        $response = $this->controller->getImageByPreset(
            request(),
            'xs',
            'contain',
            $signature,
            ltrim($asset->url(), '/'),
            '.jpg'
        );

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertEquals('image/jpeg', $response->headers->get('Content-Type'));
    }

    #[Test]
    public function it_can_know_the_right_mime_type_for_png(): void
    {
        $asset = $this->uploadTestAsset('upload.png');

        $params = [
            's' => '',
            'p' => 'xs',
            'fit' => 'contain',
            'format' => '.png',
        ];

        $signatureFactory = SignatureFactory::create(config('app.key'));
        $signed = $signatureFactory->addSignature($asset->url(), $params);
        $signature = $signed['s'];

        $cachePath = config('statamic.assets.image_manipulation.cache_path');
        $storagePrefix = config('justbetter.glide-directive.storage_prefix');
        $expectedImagePath = $cachePath.'/'.$storagePrefix.'/xs/contain/'.$signature.$asset->url().'.png';

        $directory = dirname($expectedImagePath);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($expectedImagePath, 'fake-image-content');

        $response = $this->controller->getImageByPreset(
            request(),
            'xs',
            'contain',
            $signature,
            ltrim($asset->url(), '/'),
            '.png'
        );

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertEquals('image/png', $response->headers->get('Content-Type'));
    }

    #[Test]
    public function it_can_know_the_right_mime_type_for_gif(): void
    {
        $asset = $this->uploadTestAsset('upload.gif');

        $params = [
            's' => '',
            'p' => 'xs',
            'fit' => 'contain',
            'format' => '.gif',
        ];

        $signatureFactory = SignatureFactory::create(config('app.key'));
        $signed = $signatureFactory->addSignature($asset->url(), $params);
        $signature = $signed['s'];

        $cachePath = config('statamic.assets.image_manipulation.cache_path');
        $storagePrefix = config('justbetter.glide-directive.storage_prefix');
        $expectedImagePath = $cachePath.'/'.$storagePrefix.'/xs/contain/'.$signature.$asset->url().'.gif';

        $directory = dirname($expectedImagePath);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($expectedImagePath, 'fake-image-content');

        $response = $this->controller->getImageByPreset(
            request(),
            'xs',
            'contain',
            $signature,
            ltrim($asset->url(), '/'),
            '.gif'
        );

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertEquals('image/gif', $response->headers->get('Content-Type'));
    }
}
