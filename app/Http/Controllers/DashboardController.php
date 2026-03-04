<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Lot;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $projects = Project::where('tenant_id', $tenantId)->get();

        $totalLots = 0;
        $availableLots = 0;
        $reservedLots = 0;
        $soldLots = 0;
        $totalRevenue = 0;

        foreach ($projects as $project) {
            $lots = $project->lots();
            $totalLots += $lots->count();
            $availableLots += $lots->where('status', 'available')->count();
            $reservedLots += (clone $project->lots())->where('status', 'reserved')->count();
            $soldLots += (clone $project->lots())->where('status', 'sold')->count();
        }

        // Revenue from paid payments
        $totalRevenue = Payment::whereHas('paymentPlan.reservation.lot.block.project', function ($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId);
        })->where('status', 'paid')->sum('amount');

        // Recent reservations
        $recentReservations = Reservation::with(['client', 'lot.block.project', 'user'])
            ->whereHas('lot.block.project', function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            })
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($r) {
                return [
                    'id' => $r->id,
                    'client_name' => $r->client->full_name,
                    'lot' => $r->lot->full_identifier,
                    'project' => $r->lot->block->project->name,
                    'status' => $r->status,
                    'status_label' => $r->status_label,
                    'down_payment' => (float) $r->down_payment,
                    'payment_deadline' => $r->payment_deadline->format('Y-m-d'),
                    'created_at' => $r->created_at->format('Y-m-d'),
                    'agent' => $r->user->name,
                ];
            });

        // Upcoming payments
        $upcomingPayments = Payment::with(['paymentPlan.reservation.client', 'paymentPlan.reservation.lot.block'])
            ->whereHas('paymentPlan.reservation.lot.block.project', function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            })
            ->where('status', 'pending')
            ->orderBy('due_date')
            ->take(5)
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'client_name' => $p->paymentPlan->reservation->client->full_name,
                    'lot' => $p->paymentPlan->reservation->lot->full_identifier,
                    'amount' => (float) $p->amount,
                    'due_date' => $p->due_date->format('Y-m-d'),
                    'installment' => $p->installment_number . '/' . $p->paymentPlan->total_installments,
                ];
            });

        $totalClients = Client::where('tenant_id', $tenantId)->count();

        return Inertia::render('Dashboard', [
            'stats' => [
                'totalProjects' => $projects->count(),
                'totalLots' => $totalLots,
                'availableLots' => $availableLots,
                'reservedLots' => $reservedLots,
                'soldLots' => $soldLots,
                'totalRevenue' => (float) $totalRevenue,
                'totalClients' => $totalClients,
            ],
            'recentReservations' => $recentReservations,
            'upcomingPayments' => $upcomingPayments,
            'projects' => $projects->map(function ($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'location' => $p->location,
                    'status' => $p->status,
                    'total_lots' => $p->total_lots_count,
                    'available_lots' => $p->available_lots_count,
                    'reserved_lots' => $p->reserved_lots_count,
                    'sold_lots' => $p->sold_lots_count,
                ];
            }),
        ]);
    }
}
