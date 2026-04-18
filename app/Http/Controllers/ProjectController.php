<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $projects = Project::where('tenant_id', $request->user()->tenant_id)
            ->withCount(['blocks', 'lots'])
            ->get()
            ->map(function ($project) {
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'slug' => $project->slug,
                    'location' => $project->location,
                    'municipality' => $project->municipality,
                    'department' => $project->department,
                    'total_area' => (float) $project->total_area,
                    'price_per_m2' => (float) $project->price_per_m2,
                    'status' => $project->status,
                    'blocks_count' => $project->blocks_count,
                    'lots_count' => $project->lots_count,
                    'available_lots' => $project->available_lots_count,
                    'reserved_lots' => $project->reserved_lots_count,
                    'sold_lots' => $project->sold_lots_count,
                    'image_path' => $project->image_path,
                ];
            });

        return Inertia::render('Projects/Index', [
            'projects' => $projects,
        ]);
    }

    public function show(Project $project)
    {
        $this->authorizeProject($project);

        $blocks = $project->blocks()
            ->with([
                'lots' => function ($q) {
                    $q->orderBy('lot_number');
                }
            ])
            ->orderBy('name')
            ->get()
            ->map(function ($block) {
                return [
                    'id' => $block->id,
                    'name' => $block->name,
                    'code' => $block->code,
                    'total_lots' => $block->lots->count(),
                    'available_lots' => $block->available_lots_count,
                    'reserved_lots' => $block->reserved_lots_count,
                    'sold_lots' => $block->sold_lots_count,
                    'pending_approval_lots' => $block->pending_approval_lots_count,
                    'lots' => $block->lots->map(function ($lot) {
                        return [
                            'id' => $lot->id,
                            'lot_number' => $lot->lot_number,
                            'area' => (float) $lot->area,
                            'price' => (float) $lot->price,
                            'status' => $lot->status,
                            'status_label' => $lot->status_label,
                            'status_color' => $lot->status_color,
                        ];
                    }),
                ];
            });

        return Inertia::render('Projects/Show', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'slug' => $project->slug,
                'description' => $project->description,
                'location' => $project->location,
                'municipality' => $project->municipality,
                'department' => $project->department,
                'total_area' => (float) $project->total_area,
                'price_per_m2' => (float) $project->price_per_m2,
                'status' => $project->status,
                'total_lots' => $project->total_lots_count,
                'available_lots' => $project->available_lots_count,
                'reserved_lots' => $project->reserved_lots_count,
                'sold_lots' => $project->sold_lots_count,
                'pending_approval_lots' => $project->pending_approval_lots_count,
                'map_file_url' => $project->map_file ? Storage::url($project->map_file) : null,
            ],
            'blocks' => $blocks,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeAdminAction();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location' => 'required|string|max:255',
            'municipality' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'total_area' => 'nullable|numeric|min:0',
            'price_per_m2' => 'nullable|numeric|min:0',
            'map_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'blocks' => 'nullable|array',
            'blocks.*.name' => 'required|string',
            'blocks.*.lots' => 'required|integer|min:1',
            'blocks.*.default_area' => 'nullable|numeric|min:0',
            'blocks.*.default_price' => 'nullable|numeric|min:0',
        ]);

        $validated['tenant_id'] = $request->user()->tenant_id;
        $validated['slug'] = Str::slug($validated['name']) . '-' . Str::random(5);

        if ($request->hasFile('map_file')) {
            $validated['map_file'] = $request->file('map_file')->store('projects/maps', 'public');
        }

        $project = Project::create(collect($validated)->except(['blocks'])->toArray());

        if (!empty($validated['blocks'])) {
            foreach ($validated['blocks'] as $blockData) {
                $block = $project->blocks()->create([
                    'name' => $blockData['name'],
                    'total_lots' => (int) $blockData['lots'],
                ]);

                $lotsToCreate = [];
                for ($j = 1; $j <= (int) $blockData['lots']; $j++) {
                    $lotsToCreate[] = [
                        'lot_number' => (string) $j,
                        'area' => $blockData['default_area'] ?? 0,
                        'price' => $blockData['default_price'] ?? 0,
                        'status' => 'available',
                    ];
                }
                $block->lots()->createMany($lotsToCreate);
            }
        }

        return redirect()->route('projects.index')->with('success', 'Proyecto creado exitosamente.');
    }

    public function update(Request $request, Project $project)
    {
        $this->authorizeAdminAction();
        $this->authorizeProject($project);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location' => 'required|string|max:255',
            'municipality' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'total_area' => 'nullable|numeric|min:0',
            'price_per_m2' => 'nullable|numeric|min:0',
            'status' => 'in:active,paused,completed',
            'map_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        if ($request->hasFile('map_file')) {
            if ($project->map_file) {
                Storage::disk('public')->delete($project->map_file);
            }
            $validated['map_file'] = $request->file('map_file')->store('projects/maps', 'public');
        }

        $project->update($validated);

        return redirect()->back()->with('success', 'Proyecto actualizado exitosamente.');
    }

    public function destroy(Project $project)
    {
        $this->authorizeAdminAction();
        $this->authorizeProject($project);
        
        if ($project->map_file) {
            Storage::disk('public')->delete($project->map_file);
        }
        
        $project->delete();

        return redirect()->route('projects.index')->with('success', 'Proyecto eliminado.');
    }

    private function authorizeProject(Project $project): void
    {
        if ($project->tenant_id !== request()->user()->tenant_id) {
            abort(403);
        }
    }

    private function authorizeAdminAction(): void
    {
        if (!in_array(request()->user()->role, ['admin', 'accountant'])) {
            abort(403, 'No tienes permiso para gestionar proyectos.');
        }
    }
}
