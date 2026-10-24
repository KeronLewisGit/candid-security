<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());
        Paginator::defaultView('components.pagination');

        // Never serve plain HTTP in production.
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        // Crews often apply together on one office or event Wi-Fi, so the per-network
        // cap is generous; the tight cap is per phone number.
        RateLimiter::for('forms', function (Request $request) {
            $phone = preg_replace('/\D+/', '', input_string($request->input('phone')));
            $limits = [Limit::perMinute(40)->by('ip|'.$request->ip())];
            // Email-only inquiries have no phone; they share only the per-network cap.
            if ($phone !== '') {
                $limits[] = Limit::perMinute(5)->by('phone|'.$phone.'|'.$request->ip());
            }

            return $limits;
        });
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by(strtolower(input_string($request->input('email'))).'|'.$request->ip()));
    }
}
