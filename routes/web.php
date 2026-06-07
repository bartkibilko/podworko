<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\MagicLinkController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NeighbourhoodController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Passwordless magic-link authentication.
Route::get('/login', [MagicLinkController::class, 'create'])->name('login');
Route::post('/login', [MagicLinkController::class, 'store'])
    ->middleware('throttle:magic-link')
    ->name('login.store');
// GET shows a confirm page (so link prefetchers / mail scanners can't burn the
// single-use token); the POST actually consumes it and logs in.
Route::get('/login/verify', [MagicLinkController::class, 'confirm'])->name('login.verify');
Route::post('/login/verify', [MagicLinkController::class, 'verify'])->name('login.verify.store');
Route::view('/login/sent', 'auth.link-sent')->name('login.sent');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [MagicLinkController::class, 'destroy'])->name('logout');

    // S-01: founder creates a neighbourhood (name → code preview/regenerate → save).
    Route::get('/neighbourhoods/create', [NeighbourhoodController::class, 'create'])->name('neighbourhoods.create');
    Route::post('/neighbourhoods/preview', [NeighbourhoodController::class, 'preview'])->name('neighbourhoods.preview');
    Route::post('/neighbourhoods', [NeighbourhoodController::class, 'store'])->name('neighbourhoods.store');
});
