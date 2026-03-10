<?php

namespace App\Http\Controllers;

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
        ], [
            'payment_deadline.after' => 'La fecha límite de consignación debe ser posterior a hoy.',
            'start_date.after_or_equal' => 'El inicio del cobro no puede ser previo a la fecha actual.',
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

        // Create reservation
        $reservation = Reservation::create([
            'lot_id' => $validated['lot_id'],
            'client_id' => $validated['client_id'],
            'user_id' => $request->user()->id,
            'down_payment' => $validated['down_payment'],
            'payment_deadline' => $validated['payment_deadline'],
            'payment_proof' => $proofPath,
            'notes' => $validated['notes'] ?? null,
            'status' => 'pending_approval',
        ]);

        // Update lot status
        $lot->update(['status' => 'pending_approval']);

        // Generate payment plan
        $amortization = new AmortizationService();
        $amortization->generateSchedule(
            totalPrice: (float) $lot->price,
            downPayment: (float) $validated['down_payment'],
            totalInstallments: $validated['total_installments'],
            startDate: $validated['start_date'],
            reservationId: $reservation->id,
        );

        return redirect()->route('lots.show', $lot)->with('success', 'Reserva creada y pendiente de aprobación.');
    }

    public function cancel(Reservation $reservation)
    {
        $tenantId = request()->user()->tenant_id;
        if ($reservation->lot->block->project->tenant_id !== $tenantId) {
            abort(403);
        }

        if (!in_array($reservation->status, ['active'])) {
            return redirect()->back()->withErrors(['status' => 'Solo se pueden cancelar reservas activas.']);
        }

        $reservation->update([
            'status' => 'cancelled',
        ]);

        $reservation->lot->update(['status' => 'available']);

        // Cancel payment plan if exists
        if ($reservation->paymentPlan) {
            $reservation->paymentPlan->update(['status' => 'cancelled']);
        }

        return redirect()->back()->with('success', 'Reserva cancelada. El lote está disponible nuevamente.');
    }

    public function confirm(Reservation $reservation)
    {
        $tenantId = request()->user()->tenant_id;
        if ($reservation->lot->block->project->tenant_id !== $tenantId) {
            abort(403);
        }

        if ($reservation->status !== 'active') {
            return redirect()->back()->withErrors(['status' => 'Solo se pueden confirmar reservas activas.']);
        }

        $reservation->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        $reservation->lot->update(['status' => 'sold']);

        return redirect()->back()->with('success', 'Reserva confirmada. El lote ha sido vendido.');
    }

    public function approve(Reservation $reservation)
    {
        $tenantId = request()->user()->tenant_id;
        if ($reservation->lot->block->project->tenant_id !== $tenantId) {
            abort(403);
        }

        if (!request()->user()->isAdmin()) {
            abort(403, 'No autorizado.');
        }

        if ($reservation->status !== 'pending_approval') {
            return redirect()->back()->withErrors(['status' => 'Solo se pueden aprobar reservas pendientes.']);
        }

        $reservation->update([
            'status' => 'active',
        ]);

        $reservation->lot->update(['status' => 'reserved']);

        return redirect()->back()->with('success', 'Reserva aprobada exitosamente.');
    }
}
