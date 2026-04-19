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

        $query = PaymentPlan::with(['reservation.client', 'reservation.lot.block.project'])
            ->whereHas('reservation.lot.block.project', function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            });

        // Search filter
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $searchTerm = '%' . $request->search . '%';
                $q->whereHas('reservation.client', function ($q) use ($searchTerm) {
                    $q->where(\Illuminate\Support\Facades\DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', $searchTerm)
                        ->orWhere('document_number', 'like', $searchTerm)
                        ->orWhere('phone', 'like', $searchTerm);
                })->orWhereHas('reservation.lot', function ($q) use ($searchTerm) {
                    $q->where('lot_number', 'like', $searchTerm);
                });
            });
        }

        // Status filter
        if ($request->status) {
            if ($request->status === 'overdue') {
                $query->whereHas('payments', function ($q) {
                    $q->where('status', 'pending')
                        ->where('due_date', '<', now()->startOfDay());
                });
            } elseif ($request->status === 'pending_approval') {
                $query->whereHas('reservation', function ($q) {
                    $q->where('status', 'pending_approval');
                });
            } else {
                $query->where('status', $request->status);
            }
        }

        $plans = $query->paginate(15)
            ->withQueryString()
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
                    'reservation_status' => $plan->reservation->status,
                    'start_date' => $plan->start_date->format('Y-m-d'),
                    'created_at' => $plan->created_at->format('Y-m-d'),
                    'is_overdue' => $plan->payments()
                        ->where('status', 'pending')
                        ->where('due_date', '<', now()->startOfDay())
                        ->exists(),
                ];
            })
            ->toArray();

        // Sort: overdue first, then by date
        usort($plans['data'], function ($a, $b) {
            if ($a['is_overdue'] === $b['is_overdue']) {
                return $b['created_at'] <=> $a['created_at'];
            }
            return $b['is_overdue'] <=> $a['is_overdue'];
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
            'filters' => $request->only(['search', 'status']),
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
                'reservation_status' => $paymentPlan->reservation->status,
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

        if ($payment->paymentPlan->reservation->status === 'pending_approval') {
            return redirect()->back()->withErrors(['payment' => 'No se pueden recibir pagos porque la reserva principal aún no ha sido aprobada.']);
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

    public function cancelPlan(Request $request, PaymentPlan $paymentPlan)
    {
        $tenantId = $request->user()->tenant_id;
        
        if ($paymentPlan->reservation->lot->block->project->tenant_id !== $tenantId) {
            abort(403);
        }

        if (!in_array($request->user()->role, ['admin', 'accountant'])) {
            abort(403, 'Acción no autorizada.');
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($paymentPlan) {
            // Delete pending installments instead of updating due to db constraints
            $paymentPlan->payments()->where('status', 'pending')->delete();
            
            // Invalidate the plan (status defined in migration is 'cancelled')
            $paymentPlan->update(['status' => 'cancelled']);
            
            // Cancel the reservation
            $paymentPlan->reservation->update(['status' => 'cancelled']);
            
            // Re-release the lot
            $paymentPlan->reservation->lot->update(['status' => 'available']);
        });

        return redirect()->route('finances.index')->with('success', 'Contrato invalidado. El lote está disponible nuevamente.');
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
