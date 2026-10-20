@extends('layouts.app')
@section('title', 'Archive · Daily')

@section('content')
    <div class="mb-5 flex flex-wrap items-center gap-3">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Archive</h1>
            <p class="text-sm text-slate-500">Archived tasks are hidden from the board, focus view and progress bars.</p>
        </div>

        <div class="ml-auto flex flex-wrap items-center gap-1.5">
            <a href="{{ route('archive') }}"
               class="rounded-lg px-2.5 py-1.5 text-xs font-medium ring-1 ring-inset transition {{ ! $activeProject ? 'bg-slate-900 text-white ring-slate-900' : 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50' }}">
                All projects
            </a>
            @foreach ($projects as $project)
                @continue(! $counts->has($project->id))
                <a href="{{ route('archive', ['project' => $project->id]) }}"
                   class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium ring-1 ring-inset transition {{ $activeProject?->id === $project->id ? 'bg-slate-900 text-white ring-slate-900' : 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50' }}">
                    <span class="h-2 w-2 rounded-full" style="background-color: {{ $project->color }}"></span>
                    {{ $project->name }}
                    <span class="opacity-60">{{ $counts[$project->id] }}</span>
                </a>
            @endforeach
        </div>
    </div>

    @if ($tasks->isEmpty() && $orphans->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center">
            <p class="text-sm font-medium text-slate-700">The archive is empty.</p>
            <p class="mt-1 text-sm text-slate-500">
                Archive a card from the board or a project page to tuck it away without deleting it.
            </p>
            <a href="{{ route('board') }}" class="mt-4 inline-block rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Go to board</a>
        </div>
    @else
        <section class="rounded-2xl border border-slate-200 bg-white p-4">
            <header class="mb-3 flex items-center gap-2">
                <h2 class="text-sm font-semibold text-slate-800">Archived tasks</h2>
                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ $tasks->count() }}</span>
            </header>

            <div class="space-y-2.5">
                @forelse ($tasks as $task)
                    <x-task-card :task="$task" :show-project="! $activeProject" />
                @empty
                    <p class="text-sm text-slate-400">No archived tasks in this project.</p>
                @endforelse
            </div>
        </section>

        @if ($orphans->isNotEmpty())
            <section class="mt-4 rounded-2xl border border-slate-200 bg-white p-4">
                <header class="mb-3 flex items-center gap-2">
                    <h2 class="text-sm font-semibold text-slate-800">Archived subtasks</h2>
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ $orphans->count() }}</span>
                    <span class="ml-auto text-xs text-slate-400">Their parent task is still active</span>
                </header>

                <div class="space-y-2.5">
                    @foreach ($orphans as $task)
                        <div>
                            <p class="mb-1 pl-1 text-xs text-slate-400">
                                in <span class="font-medium text-slate-500">{{ $task->parent?->title }}</span>
                            </p>
                            <x-task-card :task="$task" :show-project="! $activeProject" />
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    @endif

    <x-task-modal :projects="$projects" />
@endsection
