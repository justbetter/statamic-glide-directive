<?php

namespace JustBetter\GlideDirective\Tests\Listeners;

use Illuminate\Cache\Repository;
use JustBetter\GlideDirective\Listeners\GlideCacheClearedListener;
use JustBetter\GlideDirective\Tests\TestCase;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Events\GlideCacheCleared;
use Statamic\Facades\Glide;

class GlideCacheClearedListenerTest extends TestCase
{
    #[Test]
    public function it_flushes_the_glide_cache_store(): void
    {
        $repository = $this->mock(Repository::class, function (MockInterface $mock): void {
            $mock->shouldReceive('flush')->once();
        });

        Glide::shouldReceive('cacheStore')
            ->once()
            ->andReturn($repository);

        app(GlideCacheClearedListener::class)->handle(new GlideCacheCleared);
    }
}
