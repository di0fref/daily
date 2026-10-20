@props(['task'])

@if ($task->due_date)
    @php
        if ($task->is_done) {
            $classes = 'bg-slate-100 text-slate-500 ring-slate-200 dark:bg-slate-800 dark:text-slate-500 dark:ring-slate-700';
        } elseif ($task->is_overdue) {
            $classes = 'bg-rose-100 text-rose-700 ring-rose-200 dark:bg-rose-500/15 dark:text-rose-300 dark:ring-rose-500/30';
        } elseif ($task->is_due_today) {
            $classes = 'bg-indigo-100 text-indigo-700 ring-indigo-200 dark:bg-indigo-500/15 dark:text-indigo-300 dark:ring-indigo-500/30';
        } else {
            $classes = 'bg-slate-100 text-slate-600 ring-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700';
        }

        $text = $task->is_due_today
            ? 'Today'
            : ($task->due_date->isSameDay(today()->addDay())
                ? 'Tomorrow'
                : $task->due_date->format('j M'));
    @endphp
    <span class="inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 text-[11px] font-medium ring-1 ring-inset {{ $classes }}"
          title="Due {{ $task->due_date->format('D j M Y') }}">
        <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        {{ $text }}{{ $task->is_overdue ? ' · overdue' : '' }}
    </span>
@endif
