@props([
    'value' => 0,
    'done' => null,
    'total' => null,
    'color' => '#6366f1',
    'projectId' => null,
    'label' => true,
    'size' => 'md',
])

@php
    $h = ['sm' => 'h-1.5', 'md' => 'h-2', 'lg' => 'h-2.5'][$size] ?? 'h-2';
@endphp

<div {{ $attributes->merge(['class' => 'w-full']) }}>
    @if ($label)
        <div class="mb-1 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
            <span @if ($projectId) data-progress-label="{{ $projectId }}" @endif>
                {{ $done !== null && $total !== null ? "$done/$total" : '' }}
            </span>
            <span class="font-medium text-slate-600 dark:text-slate-300" @if ($projectId) data-progress-pct="{{ $projectId }}" @endif>{{ $value }}%</span>
        </div>
    @endif
    <div class="{{ $h }} w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
        <div class="{{ $h }} rounded-full transition-all duration-500"
             style="width: {{ $value }}%; background-color: {{ $color }};"
             @if ($projectId) data-progress-for="{{ $projectId }}" @endif></div>
    </div>
</div>
