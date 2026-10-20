<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ArchiveController extends Controller
{
    public function index(Request $request): View
    {
        $projects = Project::orderBy('position')->orderBy('id')->get();

        $projectId = $request->integer('project') ?: null;
        $activeProject = $projectId ? $projects->firstWhere('id', $projectId) : null;

        $tasks = Task::with(['project', 'allSubtasks'])->withCount('attachments')
            ->archived()
            ->roots()
            ->when($activeProject, fn ($q) => $q->where('project_id', $activeProject->id))
            ->orderByDesc('archived_at')
            ->get();

        // Subtasks archived on their own, without their parent, still need a home.
        $orphans = Task::with(['project', 'parent'])->withCount('attachments')
            ->archived()
            ->whereNotNull('parent_id')
            ->whereHas('parent', fn ($q) => $q->whereNull('archived_at'))
            ->when($activeProject, fn ($q) => $q->where('project_id', $activeProject->id))
            ->orderByDesc('archived_at')
            ->get();

        $counts = Task::archived()
            ->roots()
            ->selectRaw('project_id, count(*) as total')
            ->groupBy('project_id')
            ->pluck('total', 'project_id');

        return view('archive.index', compact('projects', 'activeProject', 'tasks', 'orphans', 'counts'));
    }
}
