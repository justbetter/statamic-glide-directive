<?php

return [
    // Make use of the placeholder image which is generated from a glide data url.
    // This will add a blurry image as a placeholder before loading the correct size.
    'placeholder' => true,

    // The default presets used for generating the resizes.
    // If the config in statamic.assets.image_manipulation.presets is empty this will be used instead.
    'presets' => [
        'placeholder' => ['w' => 32, 'h' => 32, 'q' => 100, 'fit' => 'contain'],
        'xs' => ['w' => 320, 'h' => 320, 'q' => 100, 'fit' => 'contain'],
        'sm' => ['w' => 480, 'h' => 480, 'q' => 100, 'fit' => 'contain'],
        'md' => ['w' => 768, 'h' => 768, 'q' => 100, 'fit' => 'contain'],
        'lg' => ['w' => 1280, 'h' => 1280, 'q' => 100, 'fit' => 'contain'],
        'xl' => ['w' => 1440, 'h' => 1440, 'q' => 100, 'fit' => 'contain'],
        '2xl' => ['w' => 1680, 'h' => 1680, 'q' => 100, 'fit' => 'contain'],

        'xs-h' => ['w' => 160, 'h' => 320, 'q' => 100, 'fit' => 'contain'],
        'sm-h' => ['w' => 320, 'h' => 480, 'q' => 100, 'fit' => 'contain'],
        'md-h' => ['w' => 480, 'h' => 768, 'q' => 100, 'fit' => 'contain'],
        'lg-h' => ['w' => 768, 'h' => 1280, 'q' => 100, 'fit' => 'contain'],
        'xl-h' => ['w' => 1280, 'h' => 1440, 'q' => 100, 'fit' => 'contain'],
        '2xl-h' => ['w' => 1440, 'h' => 1680, 'q' => 100, 'fit' => 'contain'],
    ],

    // Configure which sources you would like to use.
    // Set 'webp' for WebP only.
    // Set 'mime_type' for the original image mime type.
    // Set 'both' to use both sources.
    'sources' => 'webp',

    // Set the default queue to use for generating the images.
    'default_queue' => env('STATAMIC_GLIDE_DIRECTIVE_DEFAULT_QUEUE', 'default'),
];
