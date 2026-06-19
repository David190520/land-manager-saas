<?php

namespace App\Observers;

use App\Models\Commission;
use App\Models\PaymentPlan;

class PaymentPlanObserver
{
    public function updated(PaymentPlan $plan): void
    {
        if (!$plan->wasChanged('status') || $plan->status !== 'completed') {
            return;
        }

        $plan->loadMissing('reservation.seller', 'reservation.lot.block.project');
        $reservation = $plan->reservation;

        if (!$reservation->seller_id) {
            return;
        }

        // Guard against duplicate commission
        if (Commission::where('payment_plan_id', $plan->id)->exists()) {
            return;
        }

        $seller = $reservation->seller;
        $baseAmount = (float) $plan->total_price;
        $rate = (float) $seller->commission_rate;

        Commission::create([
            'tenant_id'       => $reservation->lot->block->project->tenant_id,
            'seller_id'       => $reservation->seller_id,
            'reservation_id'  => $reservation->id,
            'payment_plan_id' => $plan->id,
            'base_amount'     => $baseAmount,
            'commission_rate' => $rate,
            'commission_amount' => round($baseAmount * $rate / 100, 2),
            'status'          => 'pending',
        ]);
    }
}
