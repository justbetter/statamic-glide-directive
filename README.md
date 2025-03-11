<a href="https://github.com/justbetter/statamic-glide-directive" title="JustBetter">
    <img src="./art/banner.png" alt="Banner">
</a>

# Statamic Glide Directive

- 🚀 Automatic responsive images - Optimizes images for all devices automatically.
- ⚡ Performance boost - Serves correctly sized images, reducing load times and bandwidth.
- 🧩 Simple syntax - Clean `@responsive` directive replaces complex `<picture>` tags.
- 🔄 WebP support - Automatically delivers modern image formats to compatible browsers.

## Installation

```bash
composer require justbetter/statamic-glide-directive
```

## Usage
This package adds a Blade directive. You can use an asset in the directive, and it will render the image according to the presets defined in the config. Here’s an example:

```php
@responsive($image, [
    'alt' => 'This is an alt text.', 
    'class' => 'some classes here',
    'loading' => 'lazy'
])
```

To allow images to change on resize, include this in your head:
```php
@include('statamic-glide-directive::partials.head')
```

We recommend generating your presets using:
```bash
php please assets:generate-presets
```

For performance, consider using Redis for your queue connection. If kept on sync, images will be generated on the fly, affecting page load times. When using Redis, images will also be created on the fly while processing jobs in the queue. If an image doesn't have a Glide preset ready, the original image URL will be used for the first page load.

To ensure that the image generation does not block the response, we're using the `dispatchAfterResponse` method when generating the resizes:
```php
GenerateGlideImageJob::dispatchAfterResponse($asset, $preset, $fit, $format);
```

## Config
The package has default configurations. By default, it will use the presets defined in this addon's config. If you've defined your asset presets in the Statamic config, those will be used.

Default config:
```php
'presets' => [
    'placeholder' => ['w' => 32, 'h' => 32, 'q' => 100, 'fit' => 'crop_focal'],
    'xs' => ['w' => 320, 'h' => 320, 'q' => 100, 'fit' => 'crop_focal'],
    'sm' => ['w' => 480, 'h' => 480, 'q' => 100, 'fit' => 'crop_focal'],
    'md' => ['w' => 768, 'h' => 768, 'q' => 100, 'fit' => 'crop_focal'],
    'lg' => ['w' => 1280, 'h' => 1280, 'q' => 100, 'fit' => 'crop_focal'],
    'xl' => ['w' => 1440, 'h' => 1440, 'q' => 100, 'fit' => 'crop_focal'],
    '2xl' => ['w' => 1680, 'h' => 1680, 'q' => 100, 'fit' => 'crop_focal'],
],
```

### Image Resize Threshold
This setting defines the threshold width for image source sets. Images wider than this threshold will be processed differently to optimize performance.

```php
'image_resize_threshold' => 480
```

### Placeholder
On page load, a small variant of the image will be loaded. To disable this, set the placeholder in the config file:
```php
'placeholder' => true,
```

### Sources
Configure which sources to use. By default, only WebP sources are used. You can also configure sources based on the image MIME type or use both.
```php
'sources' => 'webp',
```

### Publish
```bash
php artisan vendor:publish --provider="JustBetter\ImageOptimize\ServiceProvider"
```
