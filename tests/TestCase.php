<?php

namespace JustBetter\GlideDirective\Tests;

use Statamic\Testing\AddonTestCase;
use JustBetter\GlideDirective\ServiceProvider;
use Illuminate\Foundation\Testing\Concerns\InteractsWithViews;

abstract class TestCase extends AddonTestCase
{
    use InteractsWithViews;
    protected string $addonServiceProvider = ServiceProvider::class;

    protected $blade;

    protected function setUp(): void
    {
        parent::setUp();

        $this->blade = app('blade.compiler');
    }

    public function assetPath(string $file = ''): string
    {
        $path = __DIR__.'/Assets';

        if (strlen($file) > 0) {
            $path .= '/'.$file;
        }

        return $path;
    }

    protected function assertDirectiveOutput($expected, $expression, $variables = [], $message = '')
    {
        $compiled = $this->blade->compileString($expression);

        ob_start();
        extract($variables);
        eval(' ?>'.$compiled.'<?php ');

        $output = ob_get_clean();

        $this->assertSame($expected, $output, $message);
    }
}
