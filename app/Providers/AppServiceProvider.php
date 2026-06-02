<?php

namespace App\Providers;

use App\Events\ReservationCancelled;
use App\Events\ReservationConfirmed;
use App\Listeners\BroadcastAvailabilityUpdate;
use Carbon\CarbonImmutable;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        $this->configureDefaults();
        $this->configureRateLimiters();
        $this->configureAuthProviders();
        $this->configureEventListeners();
    }

    protected function configureEventListeners(): void
    {
        Event::listen(ReservationConfirmed::class, BroadcastAvailabilityUpdate::class);
        Event::listen(ReservationCancelled::class, BroadcastAvailabilityUpdate::class);
    }

    protected function configureAuthProviders(): void
    {
        Auth::provider('active-users', function ($app, array $config) {
            return new class($app['hash'], $config['model']) extends EloquentUserProvider
            {
                public function retrieveByCredentials(array $credentials): ?Authenticatable
                {
                    $user = parent::retrieveByCredentials($credentials);

                    return ($user instanceof Authenticatable && $user->is_active) ? $user : null;
                }
            };
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureRateLimiters(): void
    {
        RateLimiter::for('reservations', function (Request $request) {
            return Limit::perMinutes(10, 3)->by($request->ip());
        });
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
