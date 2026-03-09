<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use App\Models\Lot;
use Illuminate\Console\Command;

class ExpireReservations extends Command
{
    protected $signature = 'reservations:expire';

    protected $description = 'Expire reservations whose payment deadline has passed without any payment recorded';

    public function handle(): int
    {
        $expired = Reservation::where('status', 'active')
            ->where('payment_deadline', '<', now())
            ->whereDoesntHave('paymentPlan.payments', function ($query) {
                $query->where('status', 'paid');
            })
            ->get();

        $count = 0;

        foreach ($expired as $reservation) {
            /** @var \App\Models\Reservation $reservation */
            $reservation->update([
                'status' => 'expired',
                'expired_at' => now(),
            ]);

            // Now, if the first payment is not made within 20-30 days, the lot stays in its current state
            // until an Administrator manually releases it to "Available".

            $count++;
        }

        $this->info("Expired {$count} reservation(s). Lots remain in their current state.");

        return Command::SUCCESS;
    }
}
