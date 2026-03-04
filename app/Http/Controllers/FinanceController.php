<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PaymentPlan;
use App\Services\AmortizationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FinanceController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $plans = PaymentPlan::with(['reservation.client', 'reservation.lot.block.project'])
            ->whereHas('reservation.lot.block.project', function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            })
            ->latest()
            ->paginate(15)
            ->through(function ($plan) {
                return [
                    'id' => $plan->id,
                    'client_name' => $plan->reservation->client->full_name,
                    'lot' => $plan->reservation->lot->full_identifier,
                    'project' => $plan->reservation->lot->block->project->name,
                    'total_price' => (float) $plan->total_price,
                    'down_payment' => (float) $plan->down_payment,
                    'financed_amount' => (float) $plan->financed_amount,
                    'total_installments' => $plan->total_installments,
                    'paid_installments' => $plan->paid_installments_count,
                    'total_paid' => $plan->total_paid,
                    'remaining_balance' => $plan->remaining_balance,
                    'progress' => $plan->progress_percentage,
                    'status' => $plan->status,
                    'start_date' => $plan->start_date->format('Y-m-d'),
                ];
            });

        // Summary stats
        $totalFinanced = PaymentPlan::whereHas('reservation.lot.block.project', function ($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId);
        })->where('status', 'active')->sum('financed_amount');

        $totalCollected = Payment::whereHas('paymentPlan.reservation.lot.block.project', function ($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId);
        })->where('status', 'paid')->sum('amount');

        $pendingPayments = Payment::whereHas('paymentPlan.reservation.lot.block.project', function ($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId);
        })->where('status', 'pending')->count();

        return Inertia::render('Finances/Index', [
            'plans' => $plans,
            'summary' => [
                'totalFinanced' => (float) $totalFinanced,
                'totalCollected' => (float) $totalCollected,
                'pendingPayments' => $pendingPayments,
            ],
        ]);
    }

    public function showPlan(PaymentPlan $paymentPlan)
    {
        $tenantId = request()->user()->tenant_id;
        if ($paymentPlan->reservation->lot->block->project->tenant_id !== $tenantId) {
            abort(403);
        }

        $amortization = new AmortizationService();
        $table = $amortization->getAmortizationTable($paymentPlan);

        $paymentPlan->load('reservation.client', 'reservation.lot.block.project');

        return Inertia::render('Finances/PaymentPlan', [
            'plan' => [
                'id' => $paymentPlan->id,
                'total_price' => (float) $paymentPlan->total_price,
                'down_payment' => (float) $paymentPlan->down_payment,
                'financed_amount' => (float) $paymentPlan->financed_amount,
                'total_installments' => $paymentPlan->total_installments,
                'installment_amount' => (float) $paymentPlan->installment_amount,
                'total_paid' => $paymentPlan->total_paid,
                'remaining_balance' => $paymentPlan->remaining_balance,
                'progress' => $paymentPlan->progress_percentage,
                'start_date' => $paymentPlan->start_date->format('Y-m-d'),
                'status' => $paymentPlan->status,
            ],
            'client' => [
                'id' => $paymentPlan->reservation->client->id,
                'full_name' => $paymentPlan->reservation->client->full_name,
                'document_number' => $paymentPlan->reservation->client->document_number,
                'phone' => $paymentPlan->reservation->client->phone,
            ],
            'lot' => [
                'id' => $paymentPlan->reservation->lot->id,
                'full_identifier' => $paymentPlan->reservation->lot->full_identifier,
                'area' => (float) $paymentPlan->reservation->lot->area,
                'price' => (float) $paymentPlan->reservation->lot->price,
            ],
            'project' => [
                'name' => $paymentPlan->reservation->lot->block->project->name,
            ],
            'amortizationTable' => $table,
        ]);
    }

    public function recordPayment(Request $request, Payment $payment)
    {
        $tenantId = $request->user()->tenant_id;
        if ($payment->paymentPlan->reservation->lot->block->project->tenant_id !== $tenantId) {
            abort(403);
        }

        if ($payment->status === 'paid') {
            return redirect()->back()->withErrors(['payment' => 'Este pago ya fue registrado.']);
        }

        $validated = $request->validate([
            'payment_method' => 'required|in:cash,transfer,check',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $payment->update([
            'status' => 'paid',
            'paid_date' => now(),
            'payment_method' => $validated['payment_method'],
            'reference_number' => $validated['reference_number'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'received_by' => $request->user()->id,
        ]);

        // Check if this is the first payment - confirm reservation
        $plan = $payment->paymentPlan;
        $reservation = $plan->reservation;
        if ($reservation->status === 'active' && $plan->payments()->where('status', 'paid')->count() === 1) {
            $reservation->update([
                'status' => 'confirmed',
                'confirmed_at' => now(),
            ]);
            $reservation->lot->update(['status' => 'sold']);
        }

        // Check if all payments are done
        if ($plan->payments()->where('status', '!=', 'paid')->count() === 0) {
            $plan->update(['status' => 'completed']);
        }

        return redirect()->back()->with('success', 'Pago registrado exitosamente.');
    }

    public function generateReceipt(Payment $payment)
    {
        $tenantId = request()->user()->tenant_id;
        $payment->load('paymentPlan.reservation.client', 'paymentPlan.reservation.lot.block.project', 'receiver');

        if ($payment->paymentPlan->reservation->lot->block->project->tenant_id !== $tenantId) {
            abort(403);
        }

        $tenant = request()->user()->tenant;

        $data = [
            'payment' => $payment,
            'plan' => $payment->paymentPlan,
            'client' => $payment->paymentPlan->reservation->client,
            'lot' => $payment->paymentPlan->reservation->lot,
            'project' => $payment->paymentPlan->reservation->lot->block->project,
            'tenant' => $tenant,
            'remaining_balance' => $payment->paymentPlan->remaining_balance,
        ];

        $pdf = Pdf::loadView('pdf.receipt', $data);

        return $pdf->download('recibo_pago_' . $payment->id . '.pdf');
    }
}
