<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $query = Client::where('tenant_id', $tenantId);

        if ($request->user()->role === 'sales_agent') {
            $query->where('user_id', $request->user()->id);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'ilike', "%{$search}%")
                    ->orWhere('last_name', 'ilike', "%{$search}%")
                    ->orWhere('document_number', 'ilike', "%{$search}%")
                    ->orWhere('phone', 'ilike', "%{$search}%");
            });
        }

        $clients = $query->withCount('reservations')
            ->orderBy('first_name')
            ->paginate(15)
            ->through(function ($client) {
                return [
                    'id' => $client->id,
                    'full_name' => $client->full_name,
                    'first_name' => $client->first_name,
                    'last_name' => $client->last_name,
                    'document_type' => $client->document_type,
                    'document_number' => $client->document_number,
                    'email' => $client->email,
                    'phone' => $client->phone,
                    'city' => $client->city,
                    'reservations_count' => $client->reservations_count,
                ];
            });

        return Inertia::render('Clients/Index', [
            'clients' => $clients,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('Clients/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'document_type' => 'required|in:CC,CE,NIT,Pasaporte',
            'document_number' => 'required|string|unique:clients,document_number',
            'email' => 'nullable|email',
            'phone' => 'required|string|max:20',
            'phone_secondary' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'occupation' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $validated['tenant_id'] = $request->user()->tenant_id;
        $validated['user_id'] = $request->user()->id;

        $client = Client::create($validated);

        return redirect()->route('clients.show', $client)->with('success', 'Cliente creado exitosamente.');
    }

    public function show(Client $client)
    {
        $this->authorizeClient($client);

        $client->load(['reservations.lot.block.project', 'reservations.paymentPlan', 'documents']);

        $reservations = $client->reservations->map(function ($r) {
            return [
                'id' => $r->id,
                'lot' => [
                    'id' => $r->lot->id,
                    'lot_number' => $r->lot->lot_number,
                    'area' => $r->lot->area,
                    'block' => [
                        'name' => $r->lot->block->name,
                    ],
                    'project' => [
                        'name' => $r->lot->block->project->name,
                    ],
                ],
                'down_payment' => (float) $r->down_payment,
                'payment_deadline' => $r->payment_deadline ? $r->payment_deadline->format('Y-m-d') : null,
                'status' => $r->status,
                'status_label' => $r->status_label,
                'created_at' => $r->created_at->format('Y-m-d'),
                'payment_plan' => $r->paymentPlan ? [
                    'id' => $r->paymentPlan->id,
                    'progress' => $r->paymentPlan->progress_percentage,
                    'paid_installments' => $r->paymentPlan->paid_installments_count,
                    'total_installments' => $r->paymentPlan->total_installments,
                ] : null,
            ];
        });

        $documents = $client->documents->map(function ($d) {
            return [
                'id' => $d->id,
                'name' => $d->name,
                'type' => $d->type,
                'file_name' => $d->file_name,
                'file_size' => $d->file_size,
                'mime_type' => $d->mime_type,
                'created_at' => $d->created_at->format('Y-m-d'),
                'url' => Storage::url($d->file_path),
            ];
        });

        return Inertia::render('Clients/Show', [
            'client' => [
                'id' => $client->id,
                'first_name' => $client->first_name,
                'last_name' => $client->last_name,
                'full_name' => $client->full_name,
                'document_type' => $client->document_type,
                'document_number' => $client->document_number,
                'email' => $client->email,
                'phone' => $client->phone,
                'phone_secondary' => $client->phone_secondary,
                'address' => $client->address,
                'city' => $client->city,
                'department' => $client->department,
                'occupation' => $client->occupation,
                'notes' => $client->notes,
            ],
            'reservations' => $reservations,
            'documents' => $documents,
        ]);
    }

    public function update(Request $request, Client $client)
    {
        $this->authorizeClient($client);

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'document_type' => 'required|in:CC,CE,NIT,Pasaporte',
            'document_number' => 'required|string|unique:clients,document_number,' . $client->id,
            'email' => 'nullable|email',
            'phone' => 'required|string|max:20',
            'phone_secondary' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'occupation' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $client->update($validated);

        return redirect()->back()->with('success', 'Cliente actualizado exitosamente.');
    }

    public function uploadDocument(Request $request, Client $client)
    {
        $this->authorizeClient($client);

        $request->validate([
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'name' => 'required|string|max:255',
            'type' => 'required|in:contract,id_copy,receipt,other',
        ]);

        $file = $request->file('file');
        $path = $file->store('documents/' . $client->id, 'public');

        Document::create([
            'client_id' => $client->id,
            'name' => $request->input('name'),
            'type' => $request->input('type'),
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_by' => $request->user()->id,
        ]);

        return redirect()->back()->with('success', 'Documento subido exitosamente.');
    }

    public function deleteDocument(Client $client, Document $document)
    {
        $this->authorizeClient($client);

        if ($document->client_id !== $client->id) {
            abort(403);
        }

        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return redirect()->back()->with('success', 'Documento eliminado.');
    }

    private function authorizeClient(Client $client): void
    {
        if ($client->tenant_id !== request()->user()->tenant_id) {
            abort(403);
        }

        if (request()->user()->role === 'sales_agent' && $client->user_id !== request()->user()->id) {
            abort(403, 'No tienes permiso para ver o modificar este cliente.');
        }
    }
}
