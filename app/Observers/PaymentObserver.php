<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Payment;

class PaymentObserver
{
    public function updated(Payment $payment): void
    {
        if (! $payment->wasChanged('status') || $payment->status !== 'paid') {
            return;
        }

        $payment->loadMissing('paymentPlan.reservation.lot.block.project');

        $reservation = $payment->paymentPlan->reservation;

        AuditLog::create([
            'tenant_id'   => $reservation->lot->block->project->tenant_id,
            'user_id'     => auth()->id(),
            'client_id'   => $reservation->client_id,
            'action_type' => 'lot_payment',
            'entity_type' => 'lot',
            'entity_id'   => $reservation->lot_id,
            'description' => "Cuota #{$payment->installment_number} pagada — $" . number_format((float) $payment->amount, 0, ',', '.'),
            'new_values'  => [
                'installment_number' => $payment->installment_number,
                'amount'             => (float) $payment->amount,
                'paid_date'          => $payment->paid_date?->format('Y-m-d'),
            ],
        ]);
    }
}
