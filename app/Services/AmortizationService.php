<?php

namespace App\Services;

use App\Models\PaymentPlan;
use App\Models\Payment;
use Carbon\Carbon;

class AmortizationService
{
    /**
     * Generate a linear amortization schedule.
     * 0% interest - simple division of financed amount by number of installments.
     * The plan starts as 'pending_initial_payment' until the initial payment is recorded.
     */
    public function generateSchedule(
        float $originalPrice,
        float $downPayment,
        int $totalInstallments,
        string $startDate,
        int $reservationId,
        float $initialPaymentPercentage = 30.0,
        ?string $initialPaymentDeadline = null,
        string $discountType = 'none',
        float $discountValue = 0.0,
    ): PaymentPlan {
        // Calculate discount and final price
        $discountAmount = match ($discountType) {
            'percentage' => round($originalPrice * ($discountValue / 100), 2),
            'fixed'      => min($discountValue, $originalPrice),
            default      => 0.0,
        };
        $totalPrice = $originalPrice - $discountAmount;

        $initialPaymentAmount = round($totalPrice * ($initialPaymentPercentage / 100), 2);
        $financedAmount = $totalPrice - $downPayment - $initialPaymentAmount;
        $installmentAmount = round($financedAmount / $totalInstallments, 2);

        // Handle rounding remainder on last installment
        $lastInstallmentAmount = $financedAmount - ($installmentAmount * ($totalInstallments - 1));

        $plan = PaymentPlan::create([
            'reservation_id'              => $reservationId,
            'original_price'              => $originalPrice,
            'total_price'                 => $totalPrice,
            'discount_type'               => $discountType,
            'discount_value'              => $discountValue,
            'down_payment'                => $downPayment,
            'financed_amount'             => $financedAmount,
            'total_installments'          => $totalInstallments,
            'installment_amount'          => $installmentAmount,
            'interest_rate'               => 0,
            'start_date'                  => $startDate,
            'status'                      => 'pending_initial_payment',
            'initial_payment_percentage'  => $initialPaymentPercentage,
            'initial_payment_amount'      => $initialPaymentAmount,
            'initial_payment_deadline'    => $initialPaymentDeadline ?? now()->addDays(30)->toDateString(),
            'initial_payment_paid'        => false,
        ]);

        $date = Carbon::parse($startDate);

        for ($i = 1; $i <= $totalInstallments; $i++) {
            $amount = ($i === $totalInstallments) ? $lastInstallmentAmount : $installmentAmount;

            Payment::create([
                'payment_plan_id'    => $plan->id,
                'installment_number' => $i,
                'amount'             => $amount,
                'due_date'           => $date->copy()->addMonths($i),
                'status'             => 'pending',
            ]);
        }

        return $plan->load('payments');
    }

    /**
     * Get amortization table data for display.
     */
    public function getAmortizationTable(PaymentPlan $plan): array
    {
        $payments = $plan->payments()->orderBy('installment_number')->get();
        $balance = $plan->financed_amount;
        $table = [];

        foreach ($payments as $payment) {
            $isOverdue = $payment->status === 'pending' && $payment->due_date->lt(now()->startOfDay());
            $balance -= $payment->amount;

            $table[] = [
                'installment'  => $payment->installment_number,
                'due_date'     => $payment->due_date->format('Y-m-d'),
                'amount'       => (float) $payment->amount,
                'paid_date'    => $payment->paid_date?->format('Y-m-d'),
                'status'       => $payment->status,
                'is_overdue'   => $isOverdue,
                'status_label' => $isOverdue ? 'Vencido' : $payment->status_label,
                'balance'      => round(max(0, $balance), 2),
                'payment_id'   => $payment->id,
            ];
        }

        return $table;
    }
}
