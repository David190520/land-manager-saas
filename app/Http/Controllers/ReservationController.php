<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Lot;
use App\Models\Reservation;
use App\Services\AmortizationService;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'lot_id' => 'required|exists:lots,id',
            'client_id' => 'required|exists:clients,id',
            'down_payment' => 'required|numeric|min:0',
            'payment_deadline' => 'required|date|after:today',
            'payment_proof' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'notes' => 'nullable|string',
            // Payment plan fields
            'total_installments' => 'required|integer|min:1|max:120',
            'start_date' => 'required|date|after_or_equal:today',
            // Initial payment fields
            'initial_payment_percentage' => 'required|numeric|min:1|max:100',
            'initial_payment_deadline' => 'required|date|after_or_equal:today',
        ], [
            'payment_deadline.after' => 'La fecha límite de consignación debe ser posterior a hoy.',
            'start_date.after_or_equal' => 'El inicio del cobro no puede ser previo a la fecha actual.',
            'initial_payment_deadline.after_or_equal' => 'La fecha límite de cuota inicial no puede ser anterior a hoy.',
        ]);

        $lot = Lot::with('block.project')->findOrFail($validated['lot_id']);

        // Verify tenant ownership
        if ($lot->block->project->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        // Verify lot is available
        if ($lot->status !== 'available') {
            return redirect()->back()->withErrors(['lot_id' => 'Este lote no está disponible.']);
        }

        $proofPath = null;
        if ($request->hasFile('payment_proof')) {
            $proofPath = $request->file('payment_proof')->store('payment_proofs', 'public');
        }

        // Determine initial status based on user role
        $isAdminOrAccountant = in_array($request->user()->role, ['admin', 'accountant']);
        $initialStatus = $isAdminOrAccountant ? 'active' : 'pending_approval';

        // Create reservation
        $reservation = Reservation::create([
            'lot_id' => $validated['lot_id'],
            'client_id' => $validated['client_id'],
            'user_id' => $request->user()->id,
            'down_payment' => $validated['down_payment'],
            'payment_deadline' => $validated['payment_deadline'],
            'payment_proof' => $proofPath,
            'notes' => $validated['notes'] ?? null,
            'status' => $initialStatus,
        ]);

        // Update lot status
        $lotStatus = $isAdminOrAccountant ? 'reserved' : 'pending_approval';
        $lot->update(['status' => $lotStatus]);

        // Generate payment plan
        $amortization = new AmortizationService();
        $amortization->generateSchedule(
            totalPrice: (float) $lot->price,
            downPayment: (float) $validated['down_payment'],
            totalInstallments: $validated['total_installments'],
            startDate: $validated['start_date'],
            reservationId: $reservation->id,
            initialPaymentPercentage: (float) $validated['initial_payment_percentage'],
            initialPaymentDeadline: $validated['initial_payment_deadline'],
        );

        $message = $isAdminOrAccountant
            ? 'Reserva creada y aprobada (Acceso Administrativo).'
            : 'Reserva creada y pendiente de aprobación.';

        AuditLog::create([
            'tenant_id' => $lot->block->project->tenant_id,
            'user_id' => $request->user()->id,
            'client_id' => $reservation->client_id,
            'action_type' => 'created',
            'entity_type' => Reservation::class,
            'entity_id' => $reservation->id,
            'description' => "Contrato estructurado para {$lot->full_identifier} | Enganche: $" . number_format((float)$validated['down_payment'], 0, ',', '.') . " | Cuota inicial: {$validated['initial_payment_percentage']}%",
        ]);

        return redirect()->route('projects.show', $lot->block->project_id)->with('success', $message);
    }

    public function cancel(Request $request, Reservation $reservation)
    {
        $tenantId = $request->user()->tenant_id;
        if ($reservation->lot->block->project->tenant_id !== $tenantId) {
            abort(403);
        }

        if (!in_array($request->user()->role, ['admin', 'accountant'])) {
            abort(403, 'No autorizado.');
        }

        if (!in_array($reservation->status, ['active', 'pending_approval'])) {
            return redirect()->back()->withErrors(['status' => 'Solo se pueden cancelar reservas activas o pendientes.']);
        }

        $refundDownPayment = $request->boolean('refund_down_payment', false);

        $reservation->update([
            'status' => 'cancelled',
        ]);

        $reservation->lot->update(['status' => 'available']);

        // Cancel payment plan if exists
        if ($reservation->paymentPlan) {
            $reservation->paymentPlan->update(['status' => 'cancelled']);
        }

        $refundNote = $refundDownPayment
            ? ' — Devolución de apartado autorizada.'
            : ' — Sin devolución de apartado.';

        AuditLog::create([
            'tenant_id' => $tenantId,
            'user_id' => $request->user()->id,
            'client_id' => $reservation->client_id,
            'action_type' => 'status_changed',
            'entity_type' => Reservation::class,
            'entity_id' => $reservation->id,
            'description' => "Reserva cancelada: Estado cambió a CANCELADA — Lote liberado: {$reservation->lot->full_identifier}{$refundNote}",
            'old_values' => ['status' => $reservation->getOriginal('status')],
            'new_values' => ['status' => 'cancelled'],
        ]);

        return redirect()->back()->with('success', 'Reserva cancelada. El lote está disponible nuevamente.');
    }

    public function confirm(Reservation $reservation)
    {
        $tenantId = request()->user()->tenant_id;
        if ($reservation->lot->block->project->tenant_id !== $tenantId) {
            abort(403);
        }

        if (!in_array(request()->user()->role, ['admin', 'accountant'])) {
            abort(403, 'No autorizado.');
        }

        if ($reservation->status !== 'active') {
            return redirect()->back()->withErrors(['status' => 'Solo se pueden confirmar reservas activas.']);
        }

        $reservation->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        $reservation->lot->update(['status' => 'sold']);

        AuditLog::create([
            'tenant_id' => $tenantId,
            'user_id' => request()->user()->id,
            'client_id' => $reservation->client_id,
            'action_type' => 'status_changed',
            'entity_type' => Reservation::class,
            'entity_id' => $reservation->id,
            'description' => "Reserva confirmada. El lote {$reservation->lot->full_identifier} ha sido marcado como vendido.",
            'old_values' => ['status' => 'active'],
            'new_values' => ['status' => 'confirmed'],
        ]);

        return redirect()->back()->with('success', 'Reserva confirmada. El lote ha sido vendido.');
    }

    public function approve(Reservation $reservation)
    {
        $tenantId = request()->user()->tenant_id;
        if ($reservation->lot->block->project->tenant_id !== $tenantId) {
            abort(403);
        }

        if (!in_array(request()->user()->role, ['admin', 'accountant'])) {
            abort(403, 'No autorizado.');
        }

        if ($reservation->status !== 'pending_approval') {
            return redirect()->back()->withErrors(['status' => 'Solo se pueden aprobar reservas pendientes.']);
        }

        $reservation->update([
            'status' => 'active',
        ]);

        $reservation->lot->update(['status' => 'reserved']);

        AuditLog::create([
            'tenant_id' => $tenantId,
            'user_id' => request()->user()->id,
            'client_id' => $reservation->client_id,
            'action_type' => 'status_changed',
            'entity_type' => Reservation::class,
            'entity_id' => $reservation->id,
            'description' => "Reserva auditada y aprobada: Estado cambió a ACTIVA — {$reservation->lot->full_identifier}",
            'old_values' => ['status' => 'pending_approval'],
            'new_values' => ['status' => 'active'],
        ]);

        return redirect()->back()->with('success', 'Reserva aprobada exitosamente.');
    }
}
