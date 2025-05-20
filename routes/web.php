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

Route::get(
    config('justbetter.glide-directive.cache_prefix', 'img').'/'.config('justbetter.glide-directive.storage_prefix', 'glide-image').'/{preset}/{fit}/{s}/{file}{format}',
    [ImageController::class, 'getImageByPreset']
)
    ->where($patterns)
    ->name('glide-image.preset');
