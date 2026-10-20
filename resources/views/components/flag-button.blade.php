@props(['task'])

<span class="{{ $task->flagged ? 'text-amber-500' : 'text-slate-300 dark:text-slate-600' }}" title="{{ $task->flagged ? 'Flagged' : 'Not flagged' }}">
    <svg class="h-3.5 w-3.5" fill="{{ $task->flagged ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M3 21V4a1 1 0 011-1h12l-2 4 2 4H4"/>
    </svg>
</span>
