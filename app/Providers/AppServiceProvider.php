<?php

namespace App\Providers;

use App\Models\Payment;
use App\Models\PaymentPlan;
use App\Models\Reservation;
use App\Observers\PaymentObserver;
use App\Observers\PaymentPlanObserver;
use App\Observers\ReservationObserver;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        Reservation::observe(ReservationObserver::class);
        Payment::observe(PaymentObserver::class);
        PaymentPlan::observe(PaymentPlanObserver::class);
    }
}
