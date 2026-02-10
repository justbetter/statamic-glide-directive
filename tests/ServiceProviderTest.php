<?php

namespace JustBetter\GlideDirective\Tests;

use JustBetter\GlideDirective\Responsive;
use PHPUnit\Framework\Attributes\Test;

class ServiceProviderTest extends TestCase
{
    #[Test]
    public function it_registers_blade_directive(): void
    {
        $compiler = app('blade.compiler');
        $directives = $compiler->getCustomDirectives();
        $this->assertArrayHasKey('responsive', $directives);
    }

    #[Test]
    public function it_registers_config(): void
    {
        $this->assertNotNull(config('justbetter.glide-directive'));
        $this->assertIsArray(config('justbetter.glide-directive.presets'));
        $this->assertIsString(config('justbetter.glide-directive.sources'));
        $this->assertIsBool(config('justbetter.glide-directive.placeholder'));
    }

    #[Test]
    public function it_registers_responsive_singleton(): void
    {
        $this->assertInstanceOf(Responsive::class, app(Responsive::class));
        $this->assertSame(app(Responsive::class), app(Responsive::class));
    }

    #[Test]
    public function it_compiles_responsive_directive(): void
    {
        $this->uploadTestAsset('upload.png');

        $compiler = app('blade.compiler');
        $template = '@responsive($asset)';
        $compiled = $compiler->compileString($template);

        $this->assertStringContainsString('Responsive::handle', $compiled);
        $this->assertStringContainsString('$asset', $compiled);
    }
}
