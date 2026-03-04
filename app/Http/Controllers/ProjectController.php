<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
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
            ],
            'blocks' => $blocks,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location' => 'required|string|max:255',
            'municipality' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'total_area' => 'nullable|numeric|min:0',
            'price_per_m2' => 'nullable|numeric|min:0',
        ]);

        $validated['tenant_id'] = $request->user()->tenant_id;
        $validated['slug'] = Str::slug($validated['name']) . '-' . Str::random(5);

        Project::create($validated);

        return redirect()->route('projects.index')->with('success', 'Proyecto creado exitosamente.');
    }

    public function update(Request $request, Project $project)
    {
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
        ]);

        $project->update($validated);

        return redirect()->back()->with('success', 'Proyecto actualizado exitosamente.');
    }

    public function destroy(Project $project)
    {
        $this->authorizeProject($project);
        $project->delete();

        return redirect()->route('projects.index')->with('success', 'Proyecto eliminado.');
    }

    private function authorizeProject(Project $project): void
    {
        if ($project->tenant_id !== request()->user()->tenant_id) {
            abort(403);
        }
    }
}
