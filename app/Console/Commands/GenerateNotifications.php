<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\InternalNotification;
use App\Models\Payment;
use App\Models\PaymentPlan;
use App\Models\Reservation;
use Illuminate\Console\Command;

class GenerateNotifications extends Command
{
    protected $signature = 'notifications:generate';
    protected $description = 'Scan the system for actionable events and generate internal notifications';

    public function handle(): int
    {
        $this->info('Generating notifications...');

        $this->checkPaymentsDueSoon();
        $this->checkOverduePayments();
        $this->checkPendingReservations();
        $this->checkExpiringContracts();

        $this->info('Notifications generated successfully.');
        return Command::SUCCESS;
    }

    /**
     * Payments due within the next 5 days — HIGH urgency
     */
    private function checkPaymentsDueSoon(): void
    {
        $payments = Payment::with(['paymentPlan.reservation.client', 'paymentPlan.reservation.lot.block.project'])
            ->where('status', 'pending')
            ->whereBetween('due_date', [now()->startOfDay(), now()->addDays(5)->endOfDay()])
            ->get();

        foreach ($payments as $payment) {
            $plan = $payment->paymentPlan;
            $client = $plan->reservation->client;
            $lot = $plan->reservation->lot;
            $project = $lot->block->project;
            $daysUntil = now()->startOfDay()->diffInDays($payment->due_date);

            $dedupKey = "payment_due_soon_{$payment->id}_" . $payment->due_date->format('Y-m-d');

            InternalNotification::firstOrCreate(
                ['dedup_key' => $dedupKey],
                [
                    'tenant_id' => $project->tenant_id,
                    'user_id' => null,
                    'type' => 'payment_due_soon',
                    'urgency' => 'high',
                    'title' => 'Pago próximo a vencer',
                    'message' => "La cuota #{$payment->installment_number} de {$client->full_name} vence en {$daysUntil} día(s) — {$lot->full_identifier}",
                    'reference_type' => Payment::class,
                    'reference_id' => $payment->id,
                    'action_url' => "/finances/plans/{$plan->id}",
                ]
            );
        }

        $this->info("  → Pagos próximos: {$payments->count()}");
    }

    /**
     * Overdue payments (past due date, still pending) — HIGH urgency
     */
    private function checkOverduePayments(): void
    {
        $plans = PaymentPlan::with(['reservation.client', 'reservation.lot.block.project', 'payments'])
            ->where('status', 'active')
            ->whereHas('payments', function ($q) {
                $q->where('status', 'pending')
                    ->where('due_date', '<', now()->startOfDay());
            })
            ->get();

        foreach ($plans as $plan) {
            $client = $plan->reservation->client;
            $lot = $plan->reservation->lot;
            $project = $lot->block->project;

            $oldestOverdue = $plan->payments()
                ->where('status', 'pending')
                ->where('due_date', '<', now()->startOfDay())
                ->orderBy('due_date')
                ->first();

            $daysOverdue = $oldestOverdue->due_date->diffInDays(now());

            // Use date-based dedup so it updates daily
            $dedupKey = "overdue_{$plan->id}_" . now()->format('Y-m-d');

            InternalNotification::firstOrCreate(
                ['dedup_key' => $dedupKey],
                [
                    'tenant_id' => $project->tenant_id,
                    'user_id' => null,
                    'type' => 'overdue_detected',
                    'urgency' => 'high',
                    'title' => 'Mora detectada',
                    'message' => "{$client->full_name} lleva {$daysOverdue} día(s) en mora — {$lot->full_identifier}",
                    'reference_type' => PaymentPlan::class,
                    'reference_id' => $plan->id,
                    'action_url' => "/finances/plans/{$plan->id}",
                ]
            );
        }

        $this->info("  → Moras detectadas: {$plans->count()}");
    }

    /**
     * Reservations pending approval — MEDIUM urgency
     */
    private function checkPendingReservations(): void
    {
        $reservations = Reservation::with(['client', 'lot.block.project'])
            ->where('status', 'pending_approval')
            ->get();

        foreach ($reservations as $reservation) {
            $client = $reservation->client;
            $lot = $reservation->lot;
            $project = $lot->block->project;

            $dedupKey = "reservation_pending_{$reservation->id}";

            InternalNotification::firstOrCreate(
                ['dedup_key' => $dedupKey],
                [
                    'tenant_id' => $project->tenant_id,
                    'user_id' => null,
                    'type' => 'reservation_pending',
                    'urgency' => 'medium',
                    'title' => 'Reserva pendiente de aprobación',
                    'message' => "{$client->full_name} reservó {$lot->full_identifier}, requiere aprobación",
                    'reference_type' => Reservation::class,
                    'reference_id' => $reservation->id,
                    'action_url' => "/lots/{$lot->id}",
                ]
            );
        }

        $this->info("  → Reservas pendientes: {$reservations->count()}");
    }

    /**
     * Contracts with payment deadline within 7 days — MEDIUM urgency
     */
    private function checkExpiringContracts(): void
    {
        $reservations = Reservation::with(['client', 'lot.block.project'])
            ->whereIn('status', ['active', 'pending_approval'])
            ->whereNotNull('payment_deadline')
            ->whereBetween('payment_deadline', [now()->startOfDay(), now()->addDays(7)->endOfDay()])
            ->get();

        foreach ($reservations as $reservation) {
            $client = $reservation->client;
            $lot = $reservation->lot;
            $project = $lot->block->project;

            $dedupKey = "contract_expiring_{$reservation->id}";

            InternalNotification::firstOrCreate(
                ['dedup_key' => $dedupKey],
                [
                    'tenant_id' => $project->tenant_id,
                    'user_id' => null,
                    'type' => 'contract_expiring',
                    'urgency' => 'medium',
                    'title' => 'Contrato próximo a vencer',
                    'message' => "Cierre obligatorio {$reservation->payment_deadline->format('Y-m-d')} — {$lot->full_identifier} {$client->full_name}",
                    'reference_type' => Reservation::class,
                    'reference_id' => $reservation->id,
                    'action_url' => "/lots/{$lot->id}",
                ]
            );
        }

        $this->info("  → Contratos por vencer: {$reservations->count()}");
    }
}
