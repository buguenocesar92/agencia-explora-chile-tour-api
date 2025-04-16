<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Repositories\Contracts\TaskRepositoryInterface;
use App\Repositories\TaskRepository;
use App\Models\Trip;
use App\Models\TourTemplate;
use App\Models\Client;
use App\Models\Payment;
use App\Models\Reservation;
use App\Observers\TripObserver;
use App\Observers\TourTemplateObserver;
use App\Observers\ClientObserver;
use App\Observers\PaymentObserver;
use App\Observers\ReservationObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {


        $this->app->bind(\App\Repositories\Contracts\ReservationRepositoryInterface::class, \App\Repositories\ReservationRepository::class);

        $this->app->bind(
            TaskRepositoryInterface::class,
            TaskRepository::class
        );

        // Client Repository
        $this->app->bind(
            \App\Repositories\Contracts\ClientRepositoryInterface::class,
            \App\Repositories\ClientRepository::class
        );

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Esto le da acceso global al usuario super-admin sin importar el permiso solicitado
        Gate::before(function ($user, $ability) {
            if ($user->hasRole('admin')) {
                return true;
            }
        });

        // Registrar observadores para soft deletes en cascada
        Trip::observe(TripObserver::class);
        TourTemplate::observe(TourTemplateObserver::class);
        Client::observe(ClientObserver::class);
        Payment::observe(PaymentObserver::class);
        Reservation::observe(ReservationObserver::class);
    }
}
