<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BoardController extends Controller
{
    public function index(Request $request): View
    {
        $projects = Project::where('archived', false)
            ->orderBy('position')->orderBy('id')->get();

        $projectId = $request->integer('project') ?: null;
        $activeProject = $projectId ? $projects->firstWhere('id', $projectId) : null;

        $tasks = Task::with(['project', 'subtasks'])->withCount('attachments')
            ->roots()
            ->notArchived()
            ->when($activeProject, fn ($q) => $q->where('project_id', $activeProject->id))
            ->when(! $activeProject, fn ($q) => $q->whereHas('project', fn ($p) => $p->where('archived', false)))
            ->orderBy('position')->orderBy('id')
            ->get();

        $columns = [];
        foreach (Task::STATUSES as $key => $label) {
            $columns[$key] = [
                'label' => $label,
                'tasks' => $key === Task::STATUS_DONE
                    ? $tasks->where('status', $key)->sortByDesc(fn ($t) => $t->completed_at ?? $t->updated_at)->values()
                    : $tasks->where('status', $key)->values(),
            ];
        }

        $stats = $this->projectStats($projects);

        return view('board.index', compact('projects', 'activeProject', 'columns', 'stats'));
    }

    private function projectStats($projects): array
    {
        $rows = Task::selectRaw('project_id, count(*) as total, sum(status = ?) as done', [Task::STATUS_DONE])
            ->notArchived()
            ->groupBy('project_id')
            ->get()
            ->keyBy('project_id');

        $stats = [];
        foreach ($projects as $project) {
            $row = $rows->get($project->id);
            $total = (int) ($row->total ?? 0);
            $done = (int) ($row->done ?? 0);
            $stats[$project->id] = [
                'total' => $total,
                'done' => $done,
                'open' => $total - $done,
                'progress' => $total ? (int) round($done / $total * 100) : 0,
            ];
        }

        return $stats;
    }
}
