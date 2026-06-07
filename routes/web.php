<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\MagicLinkController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Passwordless magic-link authentication.
Route::get('/login', [MagicLinkController::class, 'create'])->name('login');
Route::post('/login', [MagicLinkController::class, 'store'])
    ->middleware('throttle:magic-link')
    ->name('login.store');
Route::get('/login/verify', [MagicLinkController::class, 'verify'])->name('login.verify');
