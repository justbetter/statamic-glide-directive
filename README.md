# Statamic glide directive

> Blade directive to use glide with images

## How to Install

You can search for this addon in the `Tools > Addons` section of the Statamic control panel and click **install**, or run the following command from your project root:

``` bash
composer require justbetter/statamic-glide-directive
```

## Usage
This package adds a blade directive. You can put a Asset in the blade directive and it will render the image for you defined by the presets in config. A example of the directive with all the options is listed below.
```php
@responsive($image, [
    'alt' => 'This is an alt text.', 
    'class' => 'some classes here',
    'lazy' => true
])
```

## Config

The package has some default config. By default it will use the presets defined in the config of this addon. If you've defined you're asset presets in the Statamic config, that will be used.

Default config:
```php
'presets' => [
    'placeholder' => ['w' => 32, 'h' => 32, 'q' => 100, 'fit' => 'crop'],
    'xs' => ['w' => 320, 'h' => 320, 'q' => 100, 'fit' => 'crop'],
    'sm' => ['w' => 480, 'h' => 480, 'q' => 100, 'fit' => 'crop'],
    'md' => ['w' => 768, 'h' => 768, 'q' => 100, 'fit' => 'crop'],
    'lg' => ['w' => 1280, 'h' => 1280, 'q' => 100, 'fit' => 'crop'],
    'xl' => ['w' => 1440, 'h' => 1440, 'q' => 100, 'fit' => 'crop'],
    '2xl' => ['w' => 1680, 'h' => 1680, 'q' => 100, 'fit' => 'crop'],
],
```

### Placeholder
On pageload a small variant of the image will be loaded, if you don't want this you can disable the placeholder in the config file.
```php
    'placeholder' => true,
```

### Publish
```
php artisan vendor:publish --provider="JustBetter\ImageOptimize\ServiceProvider"
```