@props(['task', 'showProject' => true, 'draggable' => false])

@php
    $subtasks = $task->is_archived ? $task->allSubtasks : $task->subtasks;
    $subDone = $subtasks->where('status', 'done')->count();
    $subTotal = $subtasks->count();
    $subPct = $subTotal ? (int) round($subDone / $subTotal * 100) : 0;

    $attachmentCount = $task->attachments_count ?? $task->attachments()->count();

    $editPayload = [
        'id' => $task->id,
        'title' => $task->title,
        'notes' => $task->notes,
        'status' => $task->status,
        'priority' => $task->priority,
        'flagged' => $task->flagged,
        'due_date' => optional($task->due_date)->format('Y-m-d'),
        'project_id' => $task->project_id,
        'is_subtask' => $task->parent_id !== null,
    ];
@endphp

<div data-task-id="{{ $task->id }}"
     data-status="{{ $task->status }}"
     data-has-subtasks="{{ $subTotal ? 1 : 0 }}"
     x-data="taskCard({{ Js::from($editPayload) }})"
     @mousedown="trackPointer($event)"
     @click="openEditor($event)"
     class="group rounded-xl border border-slate-200 bg-white p-3 shadow-sm transition hover:shadow-md dark:border-slate-800 dark:bg-slate-900 dark:shadow-none dark:hover:border-slate-700 {{ $draggable ? 'cursor-grab active:cursor-grabbing' : 'cursor-pointer' }} {{ $task->is_done ? 'is-done opacity-70' : '' }} {{ $task->is_overdue ? 'border-l-4 border-l-rose-400 dark:border-l-rose-500' : '' }}">

    <div class="flex items-start gap-2.5">
        <form method="POST" action="{{ route('tasks.toggle', $task) }}" class="pt-0.5">
            @csrf
            <button type="submit"
                    title="{{ $task->is_done ? 'Mark as not done' : 'Mark as done' }}"
                    class="grid h-4.5 w-4.5 place-items-center rounded-md border transition {{ $task->is_done ? 'border-emerald-500 bg-emerald-500 text-white' : 'border-slate-300 bg-white hover:border-emerald-500 dark:border-slate-600 dark:bg-slate-800 dark:hover:border-emerald-400' }}"
                    style="height:1.125rem;width:1.125rem;">
                @if ($task->is_done)
                    <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                @endif
            </button>
        </form>

        <div class="min-w-0 flex-1">
            <p class="text-sm font-medium leading-snug text-slate-800 dark:text-slate-100 {{ $task->is_done ? 'line-through text-slate-400 dark:text-slate-500' : '' }}">
                {{ $task->title }}
            </p>

            @if ($task->notes)
                <p class="mt-0.5 line-clamp-2 text-xs text-slate-500 dark:text-slate-400">{{ $task->notes }}</p>
            @endif

            <div class="mt-2 flex flex-wrap items-center gap-1.5">
                @if ($showProject && $task->project)
                    <a href="{{ route('projects.show', $task->project) }}"
                       class="inline-flex items-center gap-1 rounded-md bg-slate-50 px-1.5 py-0.5 text-[11px] font-medium text-slate-600 ring-1 ring-inset ring-slate-200 hover:bg-slate-100 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700 dark:hover:bg-slate-700">
                        <span class="h-2 w-2 rounded-full" style="background-color: {{ $task->project->color }}"></span>
                        {{ $task->project->name }}
                    </a>
                @endif

                <x-priority-badge :priority="$task->priority" />
                <x-due-badge :task="$task" />
                @if ($task->flagged)<x-flag-button :task="$task" />@endif

                <span data-attachments-for="{{ $task->id }}"
                      class="inline-flex items-center gap-1 rounded-md bg-slate-50 px-1.5 py-0.5 text-[11px] font-medium text-slate-600 ring-1 ring-inset ring-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700 {{ $attachmentCount ? '' : 'hidden' }}"
                      title="Attachments">
                    <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48"/>
                    </svg>
                    <span data-attachments-count>{{ $attachmentCount }}</span>
                </span>

                @if ($task->is_archived)
                    <span class="inline-flex items-center gap-1 rounded-md bg-slate-200 px-1.5 py-0.5 text-[11px] font-medium text-slate-600 dark:bg-slate-700 dark:text-slate-300"
                          title="Archived {{ $task->archived_at->format('D j M Y H:i') }}">
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v1a2 2 0 01-2 2M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                        </svg>
                        Archived {{ $task->archived_at->diffForHumans() }}
                    </span>
                @endif

                @if ($subTotal)
                    <button type="button" @click="open = !open"
                            class="inline-flex items-center gap-1 rounded-md bg-slate-50 px-1.5 py-0.5 text-[11px] font-medium text-slate-600 ring-1 ring-inset ring-slate-200 hover:bg-slate-100 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700 dark:hover:bg-slate-700">
                        <svg class="h-3 w-3 transition" :class="open && 'rotate-90'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                        {{ $subDone }}/{{ $subTotal }} subtasks
                    </button>
                @endif
            </div>

            @if ($subTotal)
                <div class="mt-2 h-1 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                    <div class="h-1 rounded-full bg-emerald-500 transition-all" style="width: {{ $subPct }}%"></div>
                </div>
            @endif

            <div x-show="open" x-cloak x-collapse data-subtasks
                 class="mt-2.5 space-y-1 border-t border-slate-100 pt-2.5 dark:border-slate-800">
                @foreach ($subtasks as $sub)
                    <div class="flex items-center gap-2">
                        <form method="POST" action="{{ route('tasks.toggle', $sub) }}">
                            @csrf
                            <button type="submit"
                                    class="grid place-items-center rounded border transition {{ $sub->is_done ? 'border-emerald-500 bg-emerald-500 text-white' : 'border-slate-300 bg-white hover:border-emerald-500 dark:border-slate-600 dark:bg-slate-800 dark:hover:border-emerald-400' }}"
                                    style="height:0.875rem;width:0.875rem;">
                                @if ($sub->is_done)
                                    <svg class="h-2.5 w-2.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                @endif
                            </button>
                        </form>
                        <span class="flex-1 text-xs {{ $sub->is_done ? 'text-slate-400 line-through dark:text-slate-500' : 'text-slate-600 dark:text-slate-300' }}">{{ $sub->title }}</span>
                        <x-due-badge :task="$sub" />
                        <form method="POST" action="{{ route('tasks.destroy', $sub) }}"
                              onsubmit="return confirm('Delete subtask?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-slate-300 hover:text-rose-500 dark:text-slate-600 dark:hover:text-rose-400" title="Delete subtask">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                @endforeach

                <x-new-task-form :project-id="$task->project_id" :parent-id="$task->id" status="backlog"
                                 placeholder="Add subtask…  e.g. Draft copy friday !high" compact />
            </div>
        </div>

        <div class="flex shrink-0 items-center gap-0.5 opacity-0 transition group-hover:opacity-100">
            @if ($task->is_archived)
                <form method="POST" action="{{ route('tasks.unarchive', $task) }}">
                    @csrf
                    <button type="submit" class="rounded p-1 text-slate-400 hover:bg-emerald-50 hover:text-emerald-600 dark:text-slate-500 dark:hover:bg-emerald-500/10 dark:hover:text-emerald-400" title="Restore from archive">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M4 9a8 8 0 1114.9 4"/>
                        </svg>
                    </button>
                </form>
            @else
                <form method="POST" action="{{ route('tasks.archive', $task) }}"
                      onsubmit="return confirm('Archive this task{{ $subtasks->count() > 0 ? ' and its subtasks' : '' }}?')">
                    @csrf
                    <button type="submit" class="rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700 dark:text-slate-500 dark:hover:bg-slate-800 dark:hover:text-slate-200" title="Archive task">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v1a2 2 0 01-2 2M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                        </svg>
                    </button>
                </form>
            @endif

            <button type="button" @click.stop="edit()"
                    class="rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700 dark:text-slate-500 dark:hover:bg-slate-800 dark:hover:text-slate-200" title="Edit task">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
            </button>
            <form method="POST" action="{{ route('tasks.destroy', $task) }}" onsubmit="return confirm('Delete this task and its subtasks?')">
                @csrf @method('DELETE')
                <button type="submit" class="rounded p-1 text-slate-400 hover:bg-rose-50 hover:text-rose-600 dark:text-slate-500 dark:hover:bg-rose-500/10 dark:hover:text-rose-400" title="Delete task">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</div>
