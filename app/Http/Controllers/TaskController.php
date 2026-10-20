<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Support\TaskInputParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $input = $this->withQuickAdd($request);

        $data = validator($input, [
            'project_id' => ['required_without:parent_id', 'nullable', 'exists:projects,id'],
            'parent_id' => ['nullable', 'exists:tasks,id'],
            'title' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'status' => ['nullable', Rule::in(array_keys(Task::STATUSES))],
            'priority' => ['nullable', Rule::in(array_keys(Task::PRIORITIES))],
            'due_date' => ['nullable', 'date'],
            'flagged' => ['nullable', 'boolean'],
        ])->validate();

        if (! empty($data['parent_id'])) {
            $parent = Task::findOrFail($data['parent_id']);
            $data['project_id'] = $parent->project_id;
        }

        $data['status'] ??= Task::STATUS_BACKLOG;
        $data['priority'] ??= 'normal';
        $data['flagged'] = filter_var($input['flagged'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $data['position'] = $this->nextPosition($data['project_id'], $data['parent_id'] ?? null, $data['status']);

        if ($data['status'] === Task::STATUS_DONE) {
            $data['completed_at'] = now();
        }

        Task::create($data);

        return back()->with('status', 'Task added.');
    }

    /**
     * Preview what the quick-add syntax resolves to, for the inline hints
     * and for prefilling the full task form.
     */
    public function parse(Request $request): JsonResponse
    {
        $data = $request->validate([
            'raw' => ['nullable', 'string', 'max:5000'],
        ]);

        $parsed = (new TaskInputParser)->parse((string) ($data['raw'] ?? ''));

        return response()->json($parsed + [
            'priority_label' => $parsed['priority'] ? Task::PRIORITIES[$parsed['priority']] : null,
            'status_label' => $parsed['status'] ? Task::STATUSES[$parsed['status']] : null,
            'due_label' => $this->dueLabel($parsed['due_date']),
        ]);
    }

    /**
     * Merge quick-add syntax over the form's context values. Anything the
     * parser recognised wins; the form only supplies the defaults.
     *
     * @return array<string, mixed>
     */
    private function withQuickAdd(Request $request): array
    {
        $input = $request->except('raw', '_token', '_method');

        if (! filled($request->input('raw'))) {
            return $input;
        }

        $parsed = (new TaskInputParser)->parse((string) $request->input('raw'));

        foreach (['title', 'notes', 'project_id', 'priority', 'status', 'due_date', 'flagged'] as $key) {
            if ($parsed[$key] !== null) {
                $input[$key] = $parsed[$key];
            }
        }

        return $input;
    }

    private function dueLabel(?string $date): ?string
    {
        if ($date === null) {
            return null;
        }

        $due = Carbon::parse($date);

        return match (true) {
            $due->isToday() => 'Today',
            $due->isTomorrow() => 'Tomorrow',
            $due->year === today()->year => $due->format('D j M'),
            default => $due->format('j M Y'),
        };
    }

    public function update(Request $request, Task $task): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(array_keys(Task::STATUSES))],
            'priority' => ['required', Rule::in(array_keys(Task::PRIORITIES))],
            'due_date' => ['nullable', 'date'],
            'flagged' => ['nullable', 'boolean'],
            'project_id' => ['nullable', 'exists:projects,id'],
        ]);

        $data['flagged'] = $request->boolean('flagged');
        $data['completed_at'] = $data['status'] === Task::STATUS_DONE
            ? ($task->completed_at ?? now())
            : null;

        if ($task->parent_id !== null) {
            unset($data['project_id']);
        }

        $task->update($data);

        if ($task->parent_id === null && $task->wasChanged('project_id')) {
            $task->subtasks()->update(['project_id' => $task->project_id]);
        }

        $this->syncParent($task);

        return back()->with('status', 'Task updated.');
    }

    public function toggle(Task $task): RedirectResponse
    {
        $this->setDone($task, ! $task->is_done);

        return back();
    }

    public function destroy(Task $task): RedirectResponse
    {
        $parent = $task->parent;
        $task->delete();

        if ($parent) {
            $this->syncParent($parent->fresh());
        }

        return back()->with('status', 'Task deleted.');
    }

    public function archive(Task $task): RedirectResponse
    {
        $task->allSubtasks()->update(['archived_at' => now()]);
        $task->update(['archived_at' => now()]);

        $this->syncParent($task->fresh());

        return back()->with('status', 'Task archived.');
    }

    public function unarchive(Task $task): RedirectResponse
    {
        // Restoring a subtask must also restore its parent, or it would be orphaned.
        if ($task->parent_id !== null) {
            Task::where('id', $task->parent_id)->update(['archived_at' => null]);
        } else {
            $task->allSubtasks()->update(['archived_at' => null]);
        }

        $task->update(['archived_at' => null]);

        $this->syncParent($task->fresh());

        return back()->with('status', 'Task restored.');
    }

    /**
     * Bulk-archive every completed task, optionally scoped to one project.
     */
    public function archiveDone(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'project_id' => ['nullable', 'exists:projects,id'],
        ]);

        $ids = Task::done()
            ->notArchived()
            ->roots()
            ->when($data['project_id'] ?? null, fn ($q, $id) => $q->where('project_id', $id))
            ->pluck('id');

        if ($ids->isEmpty()) {
            return back()->with('status', 'Nothing to archive.');
        }

        Task::where(fn ($q) => $q->whereIn('id', $ids)->orWhereIn('parent_id', $ids))
            ->update(['archived_at' => now()]);

        return back()->with('status', $ids->count().' task(s) archived.');
    }

    /**
     * Kanban drag & drop: move a task to a column and reorder that column.
     */
    public function move(Request $request, Task $task): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Task::STATUSES))],
            'ordered_ids' => ['array'],
            'ordered_ids.*' => ['integer'],
        ]);

        $wasDone = $task->is_done;
        $task->status = $data['status'];

        if ($data['status'] === Task::STATUS_DONE && ! $wasDone) {
            $task->completed_at = now();
        } elseif ($data['status'] !== Task::STATUS_DONE) {
            $task->completed_at = null;
        }

        $task->save();

        foreach ($data['ordered_ids'] ?? [] as $index => $id) {
            Task::where('id', $id)->update(['position' => $index]);
        }

        if ($data['status'] === Task::STATUS_DONE) {
            $task->subtasks()->update(['status' => Task::STATUS_DONE, 'completed_at' => now()]);
        }

        $this->syncParent($task->fresh());

        $project = Project::find($task->project_id);
        $total = $project?->tasks()->count() ?? 0;
        $done = $project?->tasks()->where('status', Task::STATUS_DONE)->count() ?? 0;

        return response()->json([
            'ok' => true,
            'project' => [
                'id' => $project?->id,
                'total' => $total,
                'done' => $done,
                'progress' => $total ? (int) round($done / $total * 100) : 0,
            ],
        ]);
    }

    private function setDone(Task $task, bool $done): void
    {
        $task->status = $done ? Task::STATUS_DONE : Task::STATUS_TODAY;
        $task->completed_at = $done ? now() : null;
        $task->save();

        if ($done) {
            $task->subtasks()->update(['status' => Task::STATUS_DONE, 'completed_at' => now()]);
        }

        $this->syncParent($task->fresh());
    }

    /**
     * A parent task is only complete when every subtask is complete.
     */
    private function syncParent(?Task $task): void
    {
        if (! $task || ! $task->parent_id) {
            return;
        }

        $parent = $task->parent()->first();
        if (! $parent) {
            return;
        }

        $siblings = $parent->subtasks()->get();
        if ($siblings->isEmpty()) {
            return;
        }

        $allDone = $siblings->every(fn (Task $t) => $t->status === Task::STATUS_DONE);

        if ($allDone && ! $parent->is_done) {
            $parent->update(['status' => Task::STATUS_DONE, 'completed_at' => now()]);
        } elseif (! $allDone && $parent->is_done) {
            $parent->update(['status' => Task::STATUS_DOING, 'completed_at' => null]);
        }
    }

    private function nextPosition(int $projectId, ?int $parentId, string $status): int
    {
        return (int) Task::where('project_id', $projectId)
            ->where('parent_id', $parentId)
            ->where('status', $status)
            ->max('position') + 1;
    }
}
