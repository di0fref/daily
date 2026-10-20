@extends('layouts.app')
@section('title', 'Projects · Daily')

@section('content')
    <div class="mb-5 flex items-center gap-3">
        <div>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-slate-100">Projects</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ $projects->where('archived', false)->count() }} active</p>
        </div>
        <button type="button" x-data @click="$dispatch('new-project')"
                class="ml-auto rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
            New project
        </button>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($projects as $project)
            @php
                $total = $project->tasks_count;
                $done = $project->done_tasks_count;
                $pct = $total ? (int) round($done / $total * 100) : 0;
            @endphp
            <div class="rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900 p-4 transition hover:shadow-md {{ $project->archived ? 'opacity-60' : '' }}">
                <div class="mb-2 flex items-start gap-2">
                    <span class="mt-1 h-3 w-3 shrink-0 rounded-full" style="background-color: {{ $project->color }}"></span>
                    <div class="min-w-0 flex-1">
                        <a href="{{ route('projects.show', $project) }}" class="block truncate text-sm font-semibold text-slate-900 hover:text-indigo-700 dark:text-slate-100 dark:hover:text-indigo-400">
                            {{ $project->name }}
                        </a>
                        @if ($project->description)
                            <p class="line-clamp-2 text-xs text-slate-500 dark:text-slate-400">{{ $project->description }}</p>
                        @endif
                    </div>
                    @if ($project->archived)
                        <span class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[11px] font-medium text-slate-500 dark:bg-slate-800 dark:text-slate-400">Archived</span>
                    @endif
                </div>

                <x-progress :value="$pct" :done="$done" :total="$total" :color="$project->color" :project-id="$project->id" class="mt-3" />

                <div class="mt-3 flex items-center gap-3 text-xs text-slate-500 dark:text-slate-400">
                    <span>{{ $total - $done }} open</span>
                    <span>·</span>
                    <span>{{ $done }} done</span>
                    <a href="{{ route('board', ['project' => $project->id]) }}" class="ml-auto font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">Board →</a>
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-2xl border border-dashed border-slate-300 bg-white dark:border-slate-700 dark:bg-slate-900 p-10 text-center">
                <p class="text-sm text-slate-500 dark:text-slate-400">No projects yet. Create your first one to get started.</p>
            </div>
        @endforelse
    </div>

    <div x-data="{ open: false }" @new-project.window="open = true" @keydown.escape.window="open = false">
        <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-start justify-center bg-slate-900/40 p-4 pt-24 dark:bg-slate-950/70"
             x-transition.opacity @click.self="open = false">
            <div class="w-full max-w-md rounded-2xl bg-white p-5 shadow-xl dark:bg-slate-900 dark:ring-1 dark:ring-slate-800">
                <h3 class="mb-4 text-base font-semibold text-slate-900 dark:text-slate-100">New project</h3>
                <form method="POST" action="{{ route('projects.store') }}" class="space-y-3.5">
                    @csrf
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-300">Name</label>
                        <input type="text" name="name" required autofocus
                               class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-indigo-400 focus:outline-none focus:ring-1 focus:ring-indigo-400 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-300">Description</label>
                        <textarea name="description" rows="2"
                                  class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-indigo-400 focus:outline-none focus:ring-1 focus:ring-indigo-400 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"></textarea>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-300">Colour</label>
                        <input type="color" name="color" value="#6366f1" class="h-9 w-20 cursor-pointer rounded-lg border border-slate-200 dark:border-slate-700 dark:bg-slate-800">
                    </div>
                    <div class="flex justify-end gap-2 pt-1">
                        <button type="button" @click="open = false" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">Cancel</button>
                        <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Create</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
