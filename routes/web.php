<?php

/**
 * Because these routes should mimic static files, no middlewares are applied.
 */

use Illuminate\Support\Facades\Route;
use JustBetter\GlideDirective\Controllers\ImageController;

$patterns = [
    'file' => '.*',
    'format' => '\..+',
];

Route::get('glide-image/placeholder/{file}', [ImageController::class, 'placeholder'])
    ->where($patterns)
    ->name('glide-image.placeholder');

Route::get('storage/glide-image/{preset}/{fit}/{signature}/{file}{format}', [ImageController::class, 'getPreset'])
    ->where($patterns)
    ->name('glide-image.preset');
