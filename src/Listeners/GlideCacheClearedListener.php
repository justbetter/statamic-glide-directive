<?php

namespace JustBetter\GlideDirective\Listeners;

use Illuminate\Cache\Repository;
use Statamic\Events\GlideCacheCleared;
use Statamic\Facades\Glide;

class GlideCacheClearedListener
{
    public function handle(GlideCacheCleared $event)
    {
        /** @var Repository $repository */
        $repository = Glide::cacheStore();

        $repository->flush();
    }
}
