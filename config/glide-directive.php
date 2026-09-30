<?php

return [
    // The default widths used for generating the resizes.
    'default_widths' => [320, 480, 640, 768, 1024, 1280, 1440, 1536, 1680, 1920, 2048, 2560],

    // The default formats that are used for generating the resizes.
    'default_formats' => [
        'avif' => 'image/avif',
        'webp' => 'image/webp',
        'jpg' => 'image/jpeg',
    ],

    // The Glide/Intervention encode quality (0-100) used for every generated resize.
    // Lower this to trade image fidelity for smaller downloads.
    'quality' => 85,

    // Set the cache prefix to use for the image source sets.
    'cache_prefix' => 'img',

    // Set the storage prefix to use for the image source sets.
    'storage_prefix' => 'glide-image',
];
