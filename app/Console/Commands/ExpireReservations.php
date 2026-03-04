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
            $reservation->update([
                'status' => 'expired',
                'expired_at' => now(),
            ]);

            // Revert lot status to available
            $reservation->lot()->update(['status' => 'available']);

            $count++;
        }

        $this->info("Expired {$count} reservation(s) and reverted lot(s) to available.");

        return Command::SUCCESS;
    }
}
