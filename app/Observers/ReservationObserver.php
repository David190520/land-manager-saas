<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Reservation;

class ReservationObserver
{
    public function created(Reservation $reservation): void
    {
        $reservation->load('lot.block.project', 'client');

        AuditLog::create([
            'tenant_id'   => $reservation->lot->block->project->tenant_id,
            'user_id'     => auth()->id(),
            'client_id'   => $reservation->client_id,
            'action_type' => 'lot_reserved',
            'entity_type' => 'lot',
            'entity_id'   => $reservation->lot_id,
            'description' => "Lote apartado por {$reservation->client->full_name}",
            'new_values'  => [
                'client_id'        => $reservation->client_id,
                'client_name'      => $reservation->client->full_name,
                'down_payment'     => (float) $reservation->down_payment,
                'payment_deadline' => $reservation->payment_deadline->format('Y-m-d'),
            ],
        ]);
    }

    public function updated(Reservation $reservation): void
    {
        if (! $reservation->wasChanged('status')) {
            return;
        }

        $reservation->loadMissing('lot.block.project', 'client', 'paymentPlan.payments');

        $tenantId  = $reservation->lot->block->project->tenant_id;
        $newStatus = $reservation->status;

        if ($newStatus === 'confirmed') {
            AuditLog::create([
                'tenant_id'   => $tenantId,
                'user_id'     => auth()->id(),
                'client_id'   => $reservation->client_id,
                'action_type' => 'lot_contracted',
                'entity_type' => 'lot',
                'entity_id'   => $reservation->lot_id,
                'description' => "Contrato activado para {$reservation->client->full_name}",
                'new_values'  => [
                    'client_id'   => $reservation->client_id,
                    'client_name' => $reservation->client->full_name,
                ],
            ]);
        }

        if (in_array($newStatus, ['cancelled', 'expired'])) {
            $plan             = $reservation->paymentPlan;
            $totalPaid        = $plan ? $plan->payments->where('status', 'paid')->sum('amount') : 0;
            $installmentsPaid = $plan ? $plan->payments->where('status', 'paid')->count() : 0;
            $refund           = $reservation->refund_down_payment_flag ?? false;

            AuditLog::create([
                'tenant_id'   => $tenantId,
                'user_id'     => auth()->id(),
                'client_id'   => $reservation->client_id,
                'action_type' => 'lot_cancelled',
                'entity_type' => 'lot',
                'entity_id'   => $reservation->lot_id,
                'description' => "Reserva cancelada — {$reservation->client->full_name}",
                'new_values'  => [
                    'client_id'           => $reservation->client_id,
                    'client_name'         => $reservation->client->full_name,
                    'total_paid'          => (float) $totalPaid,
                    'installments_paid'   => $installmentsPaid,
                    'refund_down_payment' => $refund,
                ],
            ]);

            AuditLog::create([
                'tenant_id'   => $tenantId,
                'user_id'     => auth()->id(),
                'client_id'   => null,
                'action_type' => 'lot_available',
                'entity_type' => 'lot',
                'entity_id'   => $reservation->lot_id,
                'description' => 'Lote disponible nuevamente',
            ]);
        }
    }
}
