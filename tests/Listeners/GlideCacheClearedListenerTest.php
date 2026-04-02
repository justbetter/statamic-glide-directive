<?php

namespace JustBetter\GlideDirective\Tests\Listeners;

use Illuminate\Cache\Repository;
use JustBetter\GlideDirective\Listeners\GlideCacheClearedListener;
use JustBetter\GlideDirective\Tests\TestCase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Events\GlideCacheCleared;
use Statamic\Facades\Glide;

class GlideCacheClearedListenerTest extends TestCase
{
    #[Test]
    public function it_will_call_the_cache_store(): void
    {
        $repository = Mockery::mock(Repository::class);
        $repository->shouldReceive('flush')->once();

        Glide::shouldReceive('cacheStore')
            ->once()
            ->andReturn($repository);

        $listener = new GlideCacheClearedListener;

        $listener->handle(new GlideCacheCleared);
    }
}
