<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\InternalNotification;
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
        if ($search = $request->search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('reservation.client', function ($q) use ($search) {
                    $q->whereRaw("unaccent(LOWER(first_name || ' ' || last_name)) LIKE unaccent(LOWER(?))", ["%{$search}%"])
                        ->orWhereRaw("unaccent(LOWER(document_number)) LIKE unaccent(LOWER(?))", ["%{$search}%"])
                        ->orWhereRaw("unaccent(LOWER(phone)) LIKE unaccent(LOWER(?))", ["%{$search}%"]);
                })->orWhereHas('reservation.lot', function ($q) use ($search) {
                    $q->whereRaw("unaccent(LOWER(lot_number)) LIKE unaccent(LOWER(?))", ["%{$search}%"]);
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
            'payment_method' => 'required|in:cash,transfer,check,other',
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

        $plan = $payment->paymentPlan;
        $reservation = $plan->reservation;
        $client = $reservation->client;
        
        // Log transaction
        AuditLog::create([
            'tenant_id' => $tenantId,
            'user_id' => $request->user()->id,
            'client_id' => $client->id,
            'action_type' => 'payment_recorded',
            'entity_type' => Payment::class,
            'entity_id' => $payment->id,
            'description' => "Cuota #{$payment->installment_number} marcada como ABONADA — Valor: $" . number_format((float)$payment->amount, 0, ',', '.'),
        ]);
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

        // Fire info notification
        $client = $plan->reservation->client;
        InternalNotification::create([
            'tenant_id' => $tenantId,
            'user_id' => null,
            'type' => 'payment_recorded',
            'urgency' => 'info',
            'title' => 'Pago registrado exitosamente',
            'message' => "Cuota #{$payment->installment_number} de {$client->full_name} fue consignada",
            'reference_type' => Payment::class,
            'reference_id' => $payment->id,
            'action_url' => "/finances/plans/{$plan->id}",
        ]);

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
            $paymentPlan->reservation->update([
                'status' => 'cancelled' // Or however you track cancelled reservations locally
            ]);

            // Release the lot
            $paymentPlan->reservation->lot->update([
                'status' => 'available'
            ]);
            
            // Log transaction
            AuditLog::create([
                'tenant_id' => $paymentPlan->reservation->lot->block->project->tenant_id,
                'user_id' => request()->user()->id,
                'client_id' => $paymentPlan->reservation->client_id,
                'action_type' => 'status_changed',
                'entity_type' => PaymentPlan::class,
                'entity_id' => $paymentPlan->id,
                'description' => "Contrato invalidado: Estado cambió a CANCELADO — Lote liberado: {$paymentPlan->reservation->lot->full_identifier}",
                'old_values' => ['status' => 'active'],
                'new_values' => ['status' => 'cancelled'],
            ]);
        });

        return redirect()->back()->with('success', 'Contrato invalidado y lote liberado exitosamente.');
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

    public function generateCompletionCertificate(PaymentPlan $paymentPlan)
    {
        $tenantId = request()->user()->tenant_id;

        if ($paymentPlan->reservation->lot->block->project->tenant_id !== $tenantId) {
            abort(403);
        }

        if ($paymentPlan->status !== 'completed') {
            abort(403, 'El plan de pagos no está completado.');
        }

        $paymentPlan->load('reservation.client', 'reservation.lot.block.project');
        $tenant = request()->user()->tenant;

        $data = [
            'plan' => $paymentPlan,
            'client' => $paymentPlan->reservation->client,
            'lot' => $paymentPlan->reservation->lot,
            'project' => $paymentPlan->reservation->lot->block->project,
            'tenant' => $tenant,
        ];

        $pdf = Pdf::loadView('pdf.completion_certificate', $data);

        return $pdf->download('paz_y_salvo_' . $paymentPlan->id . '.pdf');
    }
}
