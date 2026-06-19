<?php

namespace App\Http\Controllers;

use App\Models\Commission;
use App\Models\Seller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SellerController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $sellers = Seller::where('tenant_id', $tenantId)
            ->withCount('commissions')
            ->orderBy('full_name')
            ->get()
            ->map(fn($s) => [
                'id'               => $s->id,
                'full_name'        => $s->full_name,
                'document_number'  => $s->document_number,
                'phone'            => $s->phone,
                'email'            => $s->email,
                'commission_rate'  => (float) $s->commission_rate,
                'is_active'        => $s->is_active,
                'commissions_count' => $s->commissions_count,
            ]);

        $commissionsQuery = Commission::with([
                'seller',
                'reservation.client',
                'reservation.lot.block.project',
                'paidByUser',
            ])
            ->whereHas('reservation.lot.block.project', fn($q) => $q->where('tenant_id', $tenantId));

        if ($request->filled('seller_id')) {
            $commissionsQuery->where('seller_id', $request->seller_id);
        }

        if ($request->filled('status')) {
            $commissionsQuery->where('status', $request->status);
        }

        $commissions = $commissionsQuery
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString()
            ->through(fn($c) => [
                'id'                => $c->id,
                'seller_id'         => $c->seller_id,
                'seller_name'       => $c->seller->full_name,
                'client_name'       => $c->reservation->client->full_name,
                'lot'               => $c->reservation->lot->full_identifier,
                'project'           => $c->reservation->lot->block->project->name,
                'base_amount'       => (float) $c->base_amount,
                'commission_rate'   => (float) $c->commission_rate,
                'commission_amount' => (float) $c->commission_amount,
                'status'            => $c->status,
                'paid_at'           => $c->paid_at?->format('Y-m-d'),
                'paid_by'           => $c->paidByUser?->name,
                'notes'             => $c->notes,
                'created_at'        => $c->created_at->format('Y-m-d'),
            ]);

        return Inertia::render('Sellers/Index', [
            'sellers'     => $sellers,
            'commissions' => $commissions,
            'filters'     => $request->only(['seller_id', 'status']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name'       => 'required|string|max:255',
            'document_number' => 'nullable|string|max:50',
            'phone'           => 'nullable|string|max:20',
            'email'           => 'nullable|email|max:255',
            'commission_rate' => 'required|numeric|min:0|max:100',
        ]);

        $validated['tenant_id'] = $request->user()->tenant_id;
        $validated['is_active'] = true;

        Seller::create($validated);

        return redirect()->back()->with('success', 'Vendedor registrado exitosamente.');
    }

    public function update(Request $request, Seller $seller)
    {
        if ($seller->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        $validated = $request->validate([
            'full_name'       => 'required|string|max:255',
            'document_number' => 'nullable|string|max:50',
            'phone'           => 'nullable|string|max:20',
            'email'           => 'nullable|email|max:255',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'is_active'       => 'boolean',
        ]);

        $seller->update($validated);

        return redirect()->back()->with('success', 'Vendedor actualizado.');
    }

    public function destroy(Seller $seller)
    {
        if ($seller->tenant_id !== request()->user()->tenant_id) {
            abort(403);
        }

        if ($seller->reservations()->exists()) {
            return redirect()->back()->withErrors(['seller' => 'No se puede eliminar un vendedor con reservas asociadas.']);
        }

        $seller->delete();

        return redirect()->back()->with('success', 'Vendedor eliminado.');
    }

    public function markCommissionPaid(Request $request, Commission $commission)
    {
        if ($commission->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        if ($commission->status === 'paid') {
            return redirect()->back()->withErrors(['commission' => 'Esta comisión ya fue pagada.']);
        }

        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        $commission->update([
            'status'  => 'paid',
            'paid_at' => now(),
            'paid_by' => $request->user()->id,
            'notes'   => $validated['notes'] ?? null,
        ]);

        return redirect()->back()->with('success', 'Comisión marcada como pagada.');
    }
}
