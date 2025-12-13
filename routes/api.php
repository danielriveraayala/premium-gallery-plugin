<?php

use Illuminate\Support\Facades\Route;
use Inmoflow\PremiumGallery\Http\Controllers\MediaController;

Route::delete('/media/{id}', [MediaController::class, 'destroy'])->name('destroy');
Route::post('/media/{id}/set-primary', [MediaController::class, 'setPrimary'])->name('set-primary');
