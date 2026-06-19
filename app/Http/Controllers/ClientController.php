<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Document;
use App\Models\InternalNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $query = Client::where('tenant_id', $tenantId);

        if ($request->user()->role === 'secretary') {
            $query->where('user_id', $request->user()->id);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw("unaccent(LOWER(first_name || ' ' || last_name)) LIKE unaccent(LOWER(?))", ["%{$search}%"])
                    ->orWhereRaw("unaccent(LOWER(document_number)) LIKE unaccent(LOWER(?))", ["%{$search}%"])
                    ->orWhereRaw("unaccent(LOWER(phone)) LIKE unaccent(LOWER(?))", ["%{$search}%"]);
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

        // Fire info notification
        InternalNotification::create([
            'tenant_id' => $request->user()->tenant_id,
            'user_id' => null,
            'type' => 'new_client',
            'urgency' => 'info',
            'title' => 'Nuevo cliente registrado',
            'message' => "{$client->full_name} fue registrado en el directorio",
            'reference_type' => Client::class,
            'reference_id' => $client->id,
            'action_url' => "/clients/{$client->id}",
        ]);

        // Audit log
        AuditLog::create([
            'tenant_id' => $request->user()->tenant_id,
            'user_id' => $request->user()->id,
            'client_id' => $client->id,
            'action_type' => 'created',
            'entity_type' => Client::class,
            'entity_id' => $client->id,
            'description' => "{$client->full_name} registrado en el directorio",
        ]);

        return redirect()->route('clients.show', $client)->with('success', 'Cliente creado exitosamente.');
    }

    public function show(Request $request, Client $client)
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
                'is_overdue' => $r->paymentPlan ? $r->paymentPlan->payments()
                    ->where('status', 'pending')
                    ->where('due_date', '<', now()->startOfDay())
                    ->exists() : false,
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

        $auditLogQuery = AuditLog::where('client_id', $client->id)
            ->with('user')
            ->orderBy('created_at', 'desc');

        if ($request->filled('log_type') && $request->log_type !== 'all') {
            $auditLogQuery->where('action_type', $request->log_type);
        }

        if ($request->filled('log_date_start')) {
            $auditLogQuery->whereDate('created_at', '>=', $request->log_date_start);
        }

        if ($request->filled('log_date_end')) {
            $auditLogQuery->whereDate('created_at', '<=', $request->log_date_end);
        }

        $auditLogs = $auditLogQuery
            ->limit(100)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'action_type' => $log->action_type,
                    'description' => $log->description,
                    'old_values' => $log->old_values,
                    'new_values' => $log->new_values,
                    'user_name' => $log->user?->name ?? 'Sistema',
                    'created_at' => $log->created_at->format('Y-m-d H:i'),
                    'created_at_human' => $log->created_at->diffForHumans(),
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
            'auditLogs' => $auditLogs,
            'logFilters' => [
                'log_type' => $request->log_type ?? 'all',
                'log_date_start' => $request->log_date_start ?? '',
                'log_date_end' => $request->log_date_end ?? '',
            ],
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

        $oldValues = $client->only(array_keys($validated));
        $client->update($validated);

        // Find what actually changed
        $changes = collect($validated)->filter(function ($value, $key) use ($oldValues) {
            return $oldValues[$key] != $value;
        });

        if ($changes->isNotEmpty()) {
            $fieldLabels = [
                'first_name' => 'Nombre', 'last_name' => 'Apellido', 'document_type' => 'Tipo Doc.',
                'document_number' => 'Nº Doc.', 'email' => 'Email', 'phone' => 'Teléfono',
                'phone_secondary' => 'Tel. Secundario', 'address' => 'Dirección', 'city' => 'Ciudad',
                'department' => 'Departamento', 'occupation' => 'Ocupación', 'notes' => 'Notas',
            ];
            $changedFields = $changes->keys()->map(fn($k) => $fieldLabels[$k] ?? $k)->implode(', ');

            AuditLog::create([
                'tenant_id' => $request->user()->tenant_id,
                'user_id' => $request->user()->id,
                'client_id' => $client->id,
                'action_type' => 'updated',
                'entity_type' => Client::class,
                'entity_id' => $client->id,
                'description' => "Datos del cliente actualizados: {$changedFields}",
                'old_values' => collect($oldValues)->only($changes->keys()->toArray())->toArray(),
                'new_values' => $changes->toArray(),
            ]);
        }

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

    public function downloadDocument(Client $client, Document $document)
    {
        $this->authorizeClient($client);

        if ($document->client_id !== $client->id) {
            abort(403);
        }

        if (!Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'El archivo no fue encontrado en el servidor.');
        }

        return Storage::disk('public')->download($document->file_path, $document->file_name);
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
