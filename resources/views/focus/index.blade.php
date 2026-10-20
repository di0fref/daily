@extends('layouts.app')
@section('title', 'Daily focus · Daily')

@section('content')
    <div class="mb-5 rounded-2xl border border-slate-200 bg-white p-5">
        <div class="flex flex-wrap items-start gap-4">
            <div>
                <h1 class="text-xl font-semibold text-slate-900">Daily focus</h1>
                <p class="text-sm text-slate-500">{{ now()->format('l, j F Y') }}</p>
            </div>

            <div class="ml-auto grid grid-cols-3 gap-3 text-center">
                <div class="rounded-xl bg-rose-50 px-4 py-2">
                    <div class="text-lg font-semibold text-rose-700">{{ $overdue->count() }}</div>
                    <div class="text-[11px] font-medium uppercase tracking-wide text-rose-500">Overdue</div>
                </div>
                <div class="rounded-xl bg-indigo-50 px-4 py-2">
                    <div class="text-lg font-semibold text-indigo-700">{{ $focusCount }}</div>
                    <div class="text-[11px] font-medium uppercase tracking-wide text-indigo-500">To do</div>
                </div>
                <div class="rounded-xl bg-emerald-50 px-4 py-2">
                    <div class="text-lg font-semibold text-emerald-700">{{ $completedToday->count() }}</div>
                    <div class="text-[11px] font-medium uppercase tracking-wide text-emerald-500">Done today</div>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <div class="mb-1 flex items-center justify-between text-xs text-slate-500">
                <span>Today's progress</span>
                <span class="font-medium text-slate-600">{{ $dayProgress }}%</span>
            </div>
            <div class="h-2 w-full overflow-hidden rounded-full bg-slate-200">
                <div class="h-2 rounded-full bg-emerald-500 transition-all duration-500" style="width: {{ $dayProgress }}%"></div>
            </div>
        </div>
    </div>

    @if ($projects->isNotEmpty())
        <div class="mb-5">
            <x-new-task-form :projects="$projects" status="today" />
        </div>
    @endif

    @php
        $sections = [
            ['title' => 'Overdue', 'tasks' => $overdue, 'tone' => 'rose', 'hint' => 'Past their due date'],
            ['title' => 'Due today', 'tasks' => $dueToday, 'tone' => 'indigo', 'hint' => 'Scheduled for today'],
            ['title' => 'Picked for today', 'tasks' => $picked, 'tone' => 'amber', 'hint' => 'In the Today / In progress columns'],
            ['title' => 'Flagged', 'tasks' => $flagged, 'tone' => 'slate', 'hint' => 'Marked important'],
        ];
        $tones = [
            'rose' => 'bg-rose-500', 'indigo' => 'bg-indigo-500',
            'amber' => 'bg-amber-500', 'slate' => 'bg-slate-400',
        ];
    @endphp

    <div class="grid gap-4 lg:grid-cols-2">
        @foreach ($sections as $section)
            @continue($section['tasks']->isEmpty())
            <section class="rounded-2xl border border-slate-200 bg-white p-4">
                <header class="mb-3 flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full {{ $tones[$section['tone']] }}"></span>
                    <h2 class="text-sm font-semibold text-slate-800">{{ $section['title'] }}</h2>
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ $section['tasks']->count() }}</span>
                    <span class="ml-auto text-xs text-slate-400">{{ $section['hint'] }}</span>
                </header>
                <div class="space-y-2.5">
                    @foreach ($section['tasks'] as $task)
                        <x-task-card :task="$task" />
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>

    @if ($focusCount === 0)
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center">
            <p class="text-2xl">🎉</p>
            <p class="mt-2 text-sm font-medium text-slate-700">Nothing on your plate today.</p>
            <p class="text-sm text-slate-500">Pull a task into the Today column on the board, or flag something important.</p>
            <a href="{{ route('board') }}" class="mt-4 inline-block rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Go to board</a>
        </div>
    @endif

    <section class="mt-5 rounded-2xl border border-slate-200 bg-white p-4" x-data="{ open: {{ $completedToday->isEmpty() ? 'false' : 'true' }} }">
        <header class="flex items-center gap-2">
            <button type="button" @click="open = !open" class="flex flex-1 items-center gap-2 text-left">
                <svg class="h-3.5 w-3.5 text-slate-400 transition" :class="open && 'rotate-90'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                <h2 class="text-sm font-semibold text-slate-800">Done today</h2>
                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700">{{ $completedToday->count() }}</span>
            </button>
        </header>

        <div x-show="open" x-cloak x-collapse class="mt-3 space-y-2.5">
            @forelse ($completedToday as $task)
                <x-task-card :task="$task" />
            @empty
                <p class="text-sm text-slate-400">Nothing completed yet today.</p>
            @endforelse
        </div>
    </section>

    <x-task-modal :projects="$projects" />
@endsection
