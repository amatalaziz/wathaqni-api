<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

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
        RateLimiter::for('auth-register', fn (Request $request) => [
            Limit::perHour(10)->by('register:ip:'.$request->ip()),
        ]);

        RateLimiter::for('auth-login', function (Request $request): array {
            $phone = $request->input('phone');
            $key = hash('sha256', is_string($phone) ? trim($phone) : 'invalid');

            return [
                Limit::perMinute(20)->by('login:ip:'.$request->ip()),
                Limit::perMinute(5)->by('login:phone:'.$key),
            ];
        });
    }
}
