@extends('layouts.app')
@section('title', $project->name . ' · Daily')

@section('content')
    <div class="mb-5 rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900 p-5" x-data="{ editing: false }">
        <div class="flex flex-wrap items-start gap-3">
            <span class="mt-1.5 h-4 w-4 shrink-0 rounded-full" style="background-color: {{ $project->color }}"></span>
            <div class="min-w-0">
                <h1 class="text-xl font-semibold text-slate-900 dark:text-slate-100">{{ $project->name }}</h1>
                @if ($project->description)
                    <p class="mt-0.5 max-w-2xl text-sm text-slate-500 dark:text-slate-400">{{ $project->description }}</p>
                @endif
            </div>

            <div class="ml-auto flex items-center gap-2">
                <a href="{{ route('board', ['project' => $project->id]) }}"
                   class="rounded-lg bg-white px-3 py-1.5 text-xs font-medium text-slate-600 ring-1 ring-inset ring-slate-200 hover:bg-slate-50 dark:bg-slate-900 dark:text-slate-300 dark:ring-slate-700 dark:hover:bg-slate-800">Board</a>
                <button type="button" @click="editing = !editing"
                        class="rounded-lg bg-white px-3 py-1.5 text-xs font-medium text-slate-600 ring-1 ring-inset ring-slate-200 hover:bg-slate-50 dark:bg-slate-900 dark:text-slate-300 dark:ring-slate-700 dark:hover:bg-slate-800">Edit</button>
                <form method="POST" action="{{ route('projects.destroy', $project) }}" onsubmit="return confirm('Delete this project and all its tasks?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="rounded-lg bg-white px-3 py-1.5 text-xs font-medium text-rose-600 ring-1 ring-inset ring-rose-200 hover:bg-rose-50 dark:bg-slate-900 dark:text-rose-400 dark:ring-rose-500/30 dark:hover:bg-rose-500/10">Delete</button>
                </form>
            </div>
        </div>

        <div class="mt-4 grid gap-4 sm:grid-cols-[1fr_auto] sm:items-end">
            <x-progress :value="$stats['progress']" :done="$stats['done']" :total="$stats['total']"
                        :color="$project->color" :project-id="$project->id" size="lg" />
            <div class="flex gap-4 text-center text-xs">
                <div><div class="text-base font-semibold text-slate-800 dark:text-slate-100">{{ $stats['open'] }}</div><div class="text-slate-500 dark:text-slate-400">Open</div></div>
                <div><div class="text-base font-semibold text-emerald-600 dark:text-emerald-400">{{ $stats['done'] }}</div><div class="text-slate-500 dark:text-slate-400">Done</div></div>
                <div><div class="text-base font-semibold {{ $stats['overdue'] ? 'text-rose-600 dark:text-rose-400' : 'text-slate-800 dark:text-slate-100' }}">{{ $stats['overdue'] }}</div><div class="text-slate-500 dark:text-slate-400">Overdue</div></div>
            </div>
        </div>

        <div x-show="editing" x-cloak x-collapse class="mt-4 border-t border-slate-100 pt-4 dark:border-slate-800">
            <form method="POST" action="{{ route('projects.update', $project) }}" class="grid gap-3 sm:grid-cols-2">
                @csrf @method('PUT')
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-300">Name</label>
                    <input type="text" name="name" value="{{ $project->name }}" required
                           class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-indigo-400 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-300">Colour</label>
                    <input type="color" name="color" value="{{ $project->color }}" class="h-9 w-20 cursor-pointer rounded-lg border border-slate-200 dark:border-slate-700 dark:bg-slate-800">
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-300">Description</label>
                    <textarea name="description" rows="2" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-indigo-400 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">{{ $project->description }}</textarea>
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                    <input type="hidden" name="archived" value="0">
                    <input type="checkbox" name="archived" value="1" @checked($project->archived) class="h-4 w-4 rounded border-slate-300 text-slate-700 dark:border-slate-600 dark:bg-slate-800">
                    Archived
                </label>
                <div class="flex justify-end sm:col-span-2">
                    <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Save project</button>
                </div>
            </form>
        </div>
    </div>

    <div class="mb-5">
        <x-new-task-form :project-id="$project->id" status="backlog" />
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900 p-4">
        <header class="mb-3 flex items-center gap-2">
            <h2 class="text-sm font-semibold text-slate-800 dark:text-slate-200">Open tasks</h2>
            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $open->count() }}</span>
        </header>

        <div class="space-y-2.5">
            @forelse ($open as $task)
                <x-task-card :task="$task" :show-project="false" />
            @empty
                <p class="text-sm text-slate-400 dark:text-slate-500">No open tasks. Add one above.</p>
            @endforelse
        </div>
    </section>

    <section class="mt-4 rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900 p-4" x-data="{ open: false }">
        <header class="flex items-center gap-2">
            <button type="button" @click="open = !open" class="flex flex-1 items-center gap-2 text-left">
                <svg class="h-3.5 w-3.5 text-slate-400 transition dark:text-slate-500" :class="open && 'rotate-90'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                <h2 class="text-sm font-semibold text-slate-800 dark:text-slate-200">Done</h2>
                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">{{ $done->count() }}</span>
            </button>

            @if ($done->isNotEmpty())
                <form method="POST" action="{{ route('tasks.archive-done') }}"
                      onsubmit="return confirm('Archive all {{ $done->count() }} completed task(s) in this project?')">
                    @csrf
                    <input type="hidden" name="project_id" value="{{ $project->id }}">
                    <button type="submit"
                            class="rounded-lg bg-white px-2.5 py-1 text-xs font-medium text-slate-600 ring-1 ring-inset ring-slate-200 hover:bg-slate-50 dark:bg-slate-900 dark:text-slate-300 dark:ring-slate-700 dark:hover:bg-slate-800">
                        Archive all
                    </button>
                </form>
            @endif
        </header>

        <div x-show="open" x-cloak x-collapse class="mt-3 space-y-2.5">
            @forelse ($done as $task)
                <x-task-card :task="$task" :show-project="false" />
            @empty
                <p class="text-sm text-slate-400 dark:text-slate-500">Nothing completed yet.</p>
            @endforelse
        </div>
    </section>

    @if ($archivedCount > 0)
        <p class="mt-4 text-center text-xs text-slate-500 dark:text-slate-400">
            {{ $archivedCount }} archived task(s) in this project ·
            <a href="{{ route('archive', ['project' => $project->id]) }}" class="font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">View archive</a>
        </p>
    @endif

    <x-task-modal :projects="collect([$project])" />
@endsection
