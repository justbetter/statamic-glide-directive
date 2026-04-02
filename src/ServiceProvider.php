<?php

namespace JustBetter\GlideDirective;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use JustBetter\GlideDirective\Listeners\GlideCacheClearedListener;
use Statamic\Events\GlideCacheCleared;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    public function boot(): void
    {
        $this
            ->bootConfig()
            ->bootDirectives()
            ->bootViews()
            ->bootRoutes()
            ->bootClasses()
            ->bootListeners();
    }

    protected function bootListeners(): static
    {
        Event::listen(GlideCacheCleared::class, GlideCacheClearedListener::class);

        return $this;
    }

    protected function bootConfig(): static
    {
        $this->mergeConfigFrom(__DIR__.'/../config/glide-directive.php', 'justbetter.glide-directive');

        $this->publishes([
            __DIR__.'/../config/glide-directive.php' => config_path('justbetter/glide-directive.php'),
        ], 'config');

        if (empty(config('statamic.assets.image_manipulation.presets'))) {
            config()->set('statamic.assets.image_manipulation.presets', config('justbetter.glide-directive.presets'));
        }

        return $this;
    }

    protected function bootDirectives(): static
    {
        Blade::directive('responsive', function ($expression) {
            return "<?php echo \JustBetter\GlideDirective\Responsive::handle({$expression}); ?>";
        });

        return $this;
    }

    protected function bootRoutes(): static
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        return $this;
    }

    protected function bootViews(): static
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'statamic-glide-directive');

        return $this;
    }

    protected function bootClasses(): static
    {
        $this->app->singleton(Responsive::class);

        return $this;
    }
}
