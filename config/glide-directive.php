<?php

return [
    // Make use of the placeholder image which is generated from a glide data url.
    // This will add a blurry image as a placeholder before loading the correct size.
    'placeholder' => true,

    // The default preset used in the img src.
    'default_preset' => 'sm',

    // The default presets used for generating the resizes.
    // If the config in statamic.assets.image_manipulation.presets is empty this will be used instead.
    'presets' => [
        'placeholder' => ['w' => 32, 'h' => 32, 'q' => 100, 'fit' => 'contain'],
        'xs' => ['w' => 320, 'h' => 320, 'q' => 70, 'fit' => 'contain', 'lossless' => false],
        'sm' => ['w' => 480, 'h' => 480, 'q' => 75, 'fit' => 'contain', 'lossless' => false],
        'md' => ['w' => 768, 'h' => 768, 'q' => 80, 'fit' => 'contain', 'lossless' => false],
        'lg' => ['w' => 1280, 'h' => 1280, 'q' => 82, 'fit' => 'contain', 'lossless' => false],
        'xl' => ['w' => 1440, 'h' => 1440, 'q' => 82, 'fit' => 'contain', 'lossless' => false],
        '2xl' => ['w' => 1680, 'h' => 1680, 'q' => 82, 'fit' => 'contain', 'lossless' => false],

        'xs-h' => ['w' => 160, 'h' => 320, 'q' => 70, 'fit' => 'contain', 'lossless' => false],
        'sm-h' => ['w' => 320, 'h' => 480, 'q' => 75, 'fit' => 'contain', 'lossless' => false],
        'md-h' => ['w' => 480, 'h' => 768, 'q' => 80, 'fit' => 'contain', 'lossless' => false],
        'lg-h' => ['w' => 768, 'h' => 1280, 'q' => 82, 'fit' => 'contain', 'lossless' => false],
        'xl-h' => ['w' => 1280, 'h' => 1440, 'q' => 82, 'fit' => 'contain', 'lossless' => false],
        '2xl-h' => ['w' => 1440, 'h' => 1680, 'q' => 82, 'fit' => 'contain', 'lossless' => false],
    ],

    // Configure which sources you would like to use.
    // Set 'webp' for WebP only.
    // Set 'mime_type' for the original image mime type.
    // Set 'both' to use both sources.
    'sources' => 'webp',

    // Set the default queue to use for generating the images.
    'default_queue' => env('STATAMIC_GLIDE_DIRECTIVE_DEFAULT_QUEUE', 'default'),

    // Set the threshold width to use for the image source sets.
    'image_resize_threshold' => 480,

    // Set the cache prefix to use for the image source sets.
    'cache_prefix' => 'img',

    // Set the storage prefix to use for the image source sets.
    'storage_prefix' => 'glide-image',
];
