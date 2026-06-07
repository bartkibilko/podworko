<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Throttle magic-link requests per email + IP to blunt email-bombing
        // on this load-bearing endpoint. Applied via `throttle:magic-link`.
        RateLimiter::for('magic-link', function (Request $request): Limit {
            $email = Str::lower((string) $request->input('email'));

            return Limit::perMinutes(15, 5)->by($email.'|'.$request->ip());
        });
    }
}
