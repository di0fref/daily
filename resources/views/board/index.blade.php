@extends('layouts.app')
@section('title', 'Board · Daily')

@section('content')
    <div class="mb-5 flex flex-wrap items-center gap-3">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Kanban board</h1>
            <p class="text-sm text-slate-500">Drag cards between columns to change status.</p>
        </div>

        <div class="ml-auto flex flex-wrap items-center gap-1.5">
            <a href="{{ route('board') }}"
               class="rounded-lg px-2.5 py-1.5 text-xs font-medium ring-1 ring-inset transition {{ ! $activeProject ? 'bg-slate-900 text-white ring-slate-900' : 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50' }}">
                All projects
            </a>
            @foreach ($projects as $project)
                <a href="{{ route('board', ['project' => $project->id]) }}"
                   class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium ring-1 ring-inset transition {{ $activeProject?->id === $project->id ? 'bg-slate-900 text-white ring-slate-900' : 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50' }}">
                    <span class="h-2 w-2 rounded-full" style="background-color: {{ $project->color }}"></span>
                    {{ $project->name }}
                </a>
            @endforeach
        </div>
    </div>

    @if ($projects->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center">
            <p class="text-sm text-slate-500">No projects yet.</p>
            <a href="{{ route('projects.index') }}" class="mt-3 inline-block rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Create a project</a>
        </div>
    @else
        <div class="mb-5">
            <x-new-task-form :projects="$projects" :project-id="$activeProject?->id" status="backlog" />
        </div>

        @if ($activeProject)
            <div class="mb-5 rounded-2xl border border-slate-200 bg-white p-4">
                <div class="mb-2 flex items-center gap-2">
                    <span class="h-3 w-3 rounded-full" style="background-color: {{ $activeProject->color }}"></span>
                    <span class="text-sm font-semibold text-slate-800">{{ $activeProject->name }} progress</span>
                    <a href="{{ route('projects.show', $activeProject) }}" class="ml-auto text-xs font-medium text-indigo-600 hover:text-indigo-800">Open project →</a>
                </div>
                <x-progress :value="$stats[$activeProject->id]['progress']"
                            :done="$stats[$activeProject->id]['done']"
                            :total="$stats[$activeProject->id]['total']"
                            :color="$activeProject->color"
                            :project-id="$activeProject->id" />
            </div>
        @else
            <div class="mb-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4 hidden">
                @foreach ($projects as $project)
                    <a href="{{ route('board', ['project' => $project->id]) }}" class="rounded-xl border border-slate-200 bg-white p-3 transition hover:shadow-md">
                        <div class="mb-1.5 flex items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $project->color }}"></span>
                            <span class="truncate text-sm font-medium text-slate-800">{{ $project->name }}</span>
                        </div>
                        <x-progress :value="$stats[$project->id]['progress']"
                                    :done="$stats[$project->id]['done']"
                                    :total="$stats[$project->id]['total']"
                                    :color="$project->color"
                                    :project-id="$project->id"
                                    size="sm" />
                    </a>
                @endforeach
            </div>
        @endif

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($columns as $key => $column)
                @php
                    $accent = [
                        'backlog' => 'bg-slate-400',
                        'today' => 'bg-indigo-500',
                        'doing' => 'bg-amber-500',
                        'done' => 'bg-emerald-500',
                    ][$key];
                @endphp
                <section class="flex flex-col rounded-2xl bg-slate-200/50 p-3">
                    <header class="mb-3 flex items-center gap-2 px-1">
                        <span class="h-2 w-2 rounded-full {{ $accent }}"></span>
                        <h2 class="text-sm font-semibold text-slate-700">{{ $column['label'] }}</h2>
                        <span class="ml-auto rounded-full bg-white px-2 py-0.5 text-xs font-medium text-slate-600"
                              data-count-for="{{ $key }}">{{ $column['tasks']->count() }}</span>

                        @if ($key === 'done' && $column['tasks']->isNotEmpty())
                            <form method="POST" action="{{ route('tasks.archive-done') }}"
                                  onsubmit="return confirm('Archive all {{ $column['tasks']->count() }} completed task(s)?')">
                                @csrf
                                @if ($activeProject)
                                    <input type="hidden" name="project_id" value="{{ $activeProject->id }}">
                                @endif
                                <button type="submit" title="Archive all completed tasks"
                                        class="rounded-md bg-white p-1 text-slate-500 transition hover:text-slate-800">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v1a2 2 0 01-2 2M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                    </svg>
                                </button>
                            </form>
                        @endif
                    </header>

                    <div class="min-h-[120px] space-y-2.5" data-column="{{ $key }}">
                        @foreach ($column['tasks'] as $task)
                            <x-task-card :task="$task" :show-project="! $activeProject" :draggable="true" />
                        @endforeach
                    </div>

                    <p data-empty class="{{ $column['tasks']->count() ? 'hidden' : '' }} px-1 pt-2 text-xs text-slate-400">
                        Drop tasks here
                    </p>
                </section>
            @endforeach
        </div>
    @endif

    <x-task-modal :projects="$projects" />
@endsection
