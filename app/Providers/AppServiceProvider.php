<?php

namespace App\Providers;

use App\Services\Thawani\ThawaniClient;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ThawaniClient::class, function () {
            return new ThawaniClient(
                rtrim((string) config('services.thawani.base_url'), '/'),
                rtrim((string) config('services.thawani.checkout_url'), '/'),
                (string) config('services.thawani.secret_key'),
                (string) config('services.thawani.publishable_key'),
            );
        });
    }

    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $email = Str::transliterate(Str::lower($request->string('email')->toString()));

            return Limit::perMinute(5)->by($email.'|'.$request->ip());
        });

        RateLimiter::for('checkout', function (Request $request) {
            return Limit::perMinute(8)->by($request->ip());
        });
    }
}
