@props(['priority' => 'normal'])

@php
    $map = [
        'urgent' => ['Urgent', 'bg-rose-100 text-rose-700 ring-rose-200 dark:bg-rose-500/15 dark:text-rose-300 dark:ring-rose-500/30'],
        'high' => ['High', 'bg-amber-100 text-amber-700 ring-amber-200 dark:bg-amber-500/15 dark:text-amber-300 dark:ring-amber-500/30'],
        'normal' => ['Normal', 'bg-slate-100 text-slate-600 ring-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700'],
        'low' => ['Low', 'bg-sky-100 text-sky-700 ring-sky-200 dark:bg-sky-500/15 dark:text-sky-300 dark:ring-sky-500/30'],
    ];
    [$label, $classes] = $map[$priority] ?? $map['normal'];
@endphp

<span class="inline-flex items-center rounded-md px-1.5 py-0.5 text-[11px] font-medium ring-1 ring-inset {{ $classes }}">
    {{ $label }}
</span>
