<?php

namespace App\Http\Controllers;

use App\Models\Lot;
use App\Models\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LotController extends Controller
{
    public function show(Lot $lot)
    {
        $lot->load(['block.project', 'activeReservation.client', 'activeReservation.user', 'activeReservation.paymentPlan.payments']);

        $tenantId = request()->user()->tenant_id;
        if ($lot->block->project->tenant_id !== $tenantId) {
            abort(403);
        }

        $clients = Client::where('tenant_id', $tenantId)
            ->orderBy('first_name')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'full_name' => $c->full_name,
                'document_number' => $c->document_number,
            ]);

        $reservation = null;
        if ($lot->activeReservation) {
            $r = $lot->activeReservation;
            $reservation = [
                'id' => $r->id,
                'client' => [
                    'id' => $r->client->id,
                    'full_name' => $r->client->full_name,
                    'document_number' => $r->client->document_number,
                    'phone' => $r->client->phone,
                    'email' => $r->client->email,
                ],
                'agent' => [
                    'id' => $r->user->id,
                    'name' => $r->user->name,
                ],
                'down_payment' => (float) $r->down_payment,
                'payment_deadline' => $r->payment_deadline->format('Y-m-d'),
                'status' => $r->status,
                'status_label' => $r->status_label,
                'payment_proof' => $r->payment_proof,
                'created_at' => $r->created_at->format('Y-m-d'),
                'payment_plan' => $r->paymentPlan ? [
                    'id' => $r->paymentPlan->id,
                    'total_price' => (float) $r->paymentPlan->total_price,
                    'down_payment' => (float) $r->paymentPlan->down_payment,
                    'financed_amount' => (float) $r->paymentPlan->financed_amount,
                    'total_installments' => $r->paymentPlan->total_installments,
                    'installment_amount' => (float) $r->paymentPlan->installment_amount,
                    'total_paid' => $r->paymentPlan->total_paid,
                    'remaining_balance' => $r->paymentPlan->remaining_balance,
                    'progress' => $r->paymentPlan->progress_percentage,
                    'status' => $r->paymentPlan->status,
                ] : null,
            ];
        }

        return Inertia::render('Lots/Show', [
            'lot' => [
                'id' => $lot->id,
                'lot_number' => $lot->lot_number,
                'area' => (float) $lot->area,
                'price' => (float) $lot->price,
                'front_length' => (float) $lot->front_length,
                'depth_length' => (float) $lot->depth_length,
                'status' => $lot->status,
                'status_label' => $lot->status_label,
                'status_color' => $lot->status_color,
                'notes' => $lot->notes,
                'block' => [
                    'id' => $lot->block->id,
                    'name' => $lot->block->name,
                ],
                'project' => [
                    'id' => $lot->block->project->id,
                    'name' => $lot->block->project->name,
                ],
            ],
            'reservation' => $reservation,
            'clients' => $clients,
        ]);
    }

    public function update(Request $request, Lot $lot)
    {
        $tenantId = $request->user()->tenant_id;
        if ($lot->block->project->tenant_id !== $tenantId) {
            abort(403);
        }

        $validated = $request->validate([
            'price' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $lot->update($validated);

        return redirect()->back()->with('success', 'Lote actualizado.');
    }
}
