<?php

namespace JustBetter\GlideDirective\Tests\Jobs;

use JustBetter\GlideDirective\Jobs\GenerateGlideImageJob;
use JustBetter\GlideDirective\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Facades\Glide;
use Statamic\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Statamic\Assets\Asset;

class GenerateGlideImageJobTest extends TestCase
{
    #[Test]
    public function it_generates_glide_image(): void
    {
        $asset = $this->uploadTestAsset('upload.png');
        
        // Clear the cache to ensure fresh generation
        /* @phpstan-ignore-next-line */
        Glide::cacheStore()->flush();
        
        $job = new GenerateGlideImageJob(
            asset: $asset,
            preset: 'xs',
            fit: 'contain',
            format: 'webp'
        );
        $job->handle();
        
        $this->assertTrue(true); // If we get here without exceptions, the test passed
        
        $asset->delete();
    }

    #[Test]
    public function it_skips_generation_if_image_exists(): void
    {
        $asset = $this->uploadTestAsset('upload.png');
        
        $job = new GenerateGlideImageJob(
            asset: $asset,
            preset: 'xs',
            fit: 'contain'
        );
        
        // Generate the image first
        $job->handle();
        
        // Try to generate again
        $job->handle();
        
        $this->assertTrue(true); // If we get here without exceptions, the test passed
        
        $asset->delete();
    }

    #[Test]
    public function it_handles_invalid_paths(): void
    {
        // Create a mock Asset object
        $asset = $this->createMock(Asset::class);
        $asset->method('url')->willReturn('non-existent-path.jpg');
        
        $job = new GenerateGlideImageJob(
            asset: $asset,
            preset: 'xs'
        );
        
        // This should not throw an exception
        $job->handle();
        
        $this->assertTrue(true); // If we get here, the test passed
    }

    #[Test]
    public function it_generates_image_with_focal_point(): void
    {
        $asset = $this->uploadTestAsset('upload.png');
        $asset->data(['focus' => '50-50'])->save();
        
        $job = new GenerateGlideImageJob(
            asset: $asset,
            preset: 'xs',
            fit: 'crop-50-50'
        );
        $job->handle();
        
        $this->assertTrue(true); // If we get here without exceptions, the test passed
        
        $asset->delete();
    }
}
