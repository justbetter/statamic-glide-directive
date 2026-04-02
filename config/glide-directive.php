<?php

return [
    // The default preset used in the img src.
    'default_preset' => 'md',

    // The default widths used for generating the resizes.
    'default_widths' => [320, 480, 640, 768, 1024, 1280, 1440, 1536, 1680],

    // The default formats that are used for generating the resizes.
    'default_formats' => [
        'avif' => 'image/avif',
        'webp' => 'image/webp',
        'jpg' => 'image/jpeg',
    ],

    'sizes' => [
        'xs' => '(min-width: 768px) 320px, (min-width: 640px) 80vw, 90vw',
        'sm' => '(min-width: 768px) 480px, (min-width: 640px) 85vw, 90vw',
        'md' => '(min-width: 1280px) 640px, (min-width: 768px) 50vw, 90vw',
        'lg' => '(min-width: 1280px) 960px, (min-width: 768px) 75vw, 90vw',
        'xl' => '(min-width: 1280px) 1150px, 90vw',
    ],

    // Set the cache prefix to use for the image source sets.
    'cache_prefix' => 'img',

    // Set the storage prefix to use for the image source sets.
    'storage_prefix' => 'glide-image',
];
