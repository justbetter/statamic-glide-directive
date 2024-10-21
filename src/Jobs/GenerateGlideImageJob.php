<?php

namespace JustBetter\GlideDirective\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Statamic\Assets\Asset;
use Illuminate\Bus\Batchable;
use Statamic\Statamic;

class GenerateGlideImageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use Batchable;

    public function __construct(
        public Asset $asset,
        public string $preset = '',
        public string $fit = '',
        public ?string $format = null,
    ) {
    }

    public function handle(): void
    {
        Statamic::tag(
            $this->preset === 'placeholder' ? 'glide:data_url' : 'glide'
        )->params(
            [
                'preset' => $this->preset,
                'src' => $this->asset->url(),
                'format' => $this->format,
                'fit' => $this->fit
            ]
        )->fetch();
    }
}
