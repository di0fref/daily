<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use Illuminate\View\View;

class FocusController extends Controller
{
    public function index(): View
    {
        $today = today();

        $base = fn () => Task::with(['project', 'subtasks'])->withCount('attachments')
            ->notArchived()
            ->whereHas('project', fn ($p) => $p->where('archived', false));

        $overdue = $base()->open()
            ->whereNotNull('due_date')->whereDate('due_date', '<', $today)
            ->orderBy('due_date')->get();

        $dueToday = $base()->open()
            ->whereDate('due_date', $today)
            ->orderByRaw("field(priority, 'urgent', 'high', 'normal', 'low')")
            ->get();

        $picked = $base()->open()
            ->whereIn('status', [Task::STATUS_TODAY, Task::STATUS_DOING])
            ->whereNotIn('id', $overdue->pluck('id')->merge($dueToday->pluck('id'))->all())
            ->orderBy('status')->orderBy('position')->get();

        $flagged = $base()->open()
            ->where('flagged', true)
            ->whereNotIn('id', $overdue->pluck('id')
                ->merge($dueToday->pluck('id'))
                ->merge($picked->pluck('id'))->all())
            ->orderByRaw("field(priority, 'urgent', 'high', 'normal', 'low')")
            ->get();

        // A subtask is already visible inside its parent's card, so drop the duplicate.
        $shownIds = $overdue->pluck('id')
            ->merge($dueToday->pluck('id'))
            ->merge($picked->pluck('id'))
            ->merge($flagged->pluck('id'))
            ->all();

        $dedupe = fn ($tasks) => $tasks
            ->reject(fn (Task $t) => $t->parent_id !== null && in_array($t->parent_id, $shownIds, true))
            ->values();

        $overdue = $dedupe($overdue);
        $dueToday = $dedupe($dueToday);
        $picked = $dedupe($picked);
        $flagged = $dedupe($flagged);

        $completedToday = Task::with(['project', 'subtasks'])->withCount('attachments')
            ->done()
            ->notArchived()
            ->whereDate('completed_at', $today)
            ->orderByDesc('completed_at')
            ->get();

        $completedIds = $completedToday->pluck('id')->all();
        $completedToday = $completedToday
            ->reject(fn (Task $t) => $t->parent_id !== null && in_array($t->parent_id, $completedIds, true))
            ->values();

        $projects = Project::where('archived', false)->orderBy('position')->orderBy('id')->get();

        $focusCount = $overdue->count() + $dueToday->count() + $picked->count() + $flagged->count();
        $doneToday = $completedToday->count();
        $dayProgress = ($focusCount + $doneToday) > 0
            ? (int) round($doneToday / ($focusCount + $doneToday) * 100)
            : 0;

        return view('focus.index', compact(
            'overdue', 'dueToday', 'picked', 'flagged',
            'completedToday', 'projects', 'focusCount', 'dayProgress'
        ));
    }
}
