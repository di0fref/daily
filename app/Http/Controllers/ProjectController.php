<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(): View
    {
        $projects = Project::withCount([
            'tasks',
            'tasks as done_tasks_count' => fn ($q) => $q->where('status', Task::STATUS_DONE),
        ])->orderBy('archived')->orderBy('position')->orderBy('id')->get();

        return view('projects.index', compact('projects'));
    }

    public function show(Project $project): View
    {
        $project->load([
            'tasks' => fn ($q) => $q->withCount('attachments')->whereNull('parent_id')->orderBy('position')->orderBy('id'),
            'tasks.subtasks',
        ]);

        $open = $project->tasks->where('status', '!=', Task::STATUS_DONE);
        $done = $project->tasks->where('status', Task::STATUS_DONE)
            ->sortByDesc(fn ($t) => $t->completed_at ?? $t->updated_at);

        $archivedCount = $project->archivedTasks()->whereNull('parent_id')->count();

        $stats = $this->stats($project);

        return view('projects.show', compact('project', 'open', 'done', 'stats', 'archivedCount'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        $data['color'] ??= '#6366f1';
        $data['position'] = (int) Project::max('position') + 1;

        $project = Project::create($data);

        return redirect()->route('projects.show', $project)->with('status', 'Project created.');
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'archived' => ['nullable', 'boolean'],
        ]);

        $data['archived'] = $request->boolean('archived');
        $project->update($data);

        return back()->with('status', 'Project updated.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();

        return redirect()->route('projects.index')->with('status', 'Project deleted.');
    }

    private function stats(Project $project): array
    {
        $all = Task::where('project_id', $project->id)->notArchived()->get();
        $total = $all->count();
        $done = $all->where('status', Task::STATUS_DONE)->count();

        return [
            'total' => $total,
            'done' => $done,
            'open' => $total - $done,
            'progress' => $total ? (int) round($done / $total * 100) : 0,
            'overdue' => $all->filter(fn ($t) => $t->is_overdue)->count(),
        ];
    }
}
