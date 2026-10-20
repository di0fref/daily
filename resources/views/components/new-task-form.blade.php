@props([
    'projects' => collect(),
    'projectId' => null,
    'status' => 'backlog',
    'parentId' => null,
    'compact' => false,
    'placeholder' => 'Add a task…  e.g. Call Anna tomorrow !high @Website #flag',
])

<form method="POST" action="{{ route('tasks.store') }}"
      x-data="quickAdd({ status: @js($status), projectId: @js($projectId), parentId: @js($parentId) })"
      {{ $attributes->merge(['class' => $compact ? 'pt-1' : 'rounded-xl border border-dashed border-slate-300 bg-white/60 p-3']) }}>
    @csrf
    <input type="hidden" name="status" value="{{ $status }}">
    @if ($projectId)
        <input type="hidden" name="project_id" value="{{ $projectId }}">
    @endif
    @if ($parentId)
        <input type="hidden" name="parent_id" value="{{ $parentId }}">
    @endif

    <div class="flex items-start gap-2">
        <textarea name="raw" x-ref="raw" x-model="raw" rows="1" required
                  placeholder="{{ $placeholder }}"
                  @focus="expanded = true"
                  @input="autoGrow(); schedule()"
                  @paste="$nextTick(() => { autoGrow(); schedule(); })"
                  @keydown.enter="submitOnEnter($event)"
                  class="min-h-[34px] flex-1 resize-none rounded-lg border border-slate-200 px-3 py-1.5 text-sm leading-6 focus:border-indigo-400 focus:outline-none focus:ring-1 focus:ring-indigo-400"></textarea>

        @unless ($projectId)
            <select name="project_id" x-model="projectId" required
                    class="w-32 shrink-0 rounded-lg border border-slate-200 px-2 py-1.5 text-xs focus:border-indigo-400 focus:outline-none">
                <option value="">Project…</option>
                @foreach ($projects as $p)
                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                @endforeach
            </select>
        @endunless

        <button type="button" @click="openFull()" title="Open the full form"
                class="shrink-0 rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-100">More…</button>
        <button type="submit" class="shrink-0 rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700">Add</button>
    </div>

    <div x-show="parsed" x-cloak class="mt-2 flex flex-wrap items-center gap-1.5 text-xs">
        <span class="text-slate-400">&rarr;</span>
        <span class="rounded-md bg-slate-100 px-2 py-0.5 font-medium text-slate-700" x-text="parsed?.title"></span>
        <template x-if="parsed?.project_name">
            <span class="rounded-md bg-indigo-50 px-2 py-0.5 text-indigo-700" x-text="'{{ '@' }}' + parsed.project_name"></span>
        </template>
        <template x-if="parsed?.due_label">
            <span class="rounded-md bg-sky-50 px-2 py-0.5 text-sky-700" x-text="parsed.due_label"></span>
        </template>
        <template x-if="parsed?.priority_label">
            <span class="rounded-md bg-rose-50 px-2 py-0.5 text-rose-700" x-text="parsed.priority_label"></span>
        </template>
        <template x-if="parsed?.status_label">
            <span class="rounded-md bg-violet-50 px-2 py-0.5 text-violet-700" x-text="parsed.status_label"></span>
        </template>
        <template x-if="parsed?.flagged">
            <span class="rounded-md bg-amber-50 px-2 py-0.5 text-amber-700">Flagged</span>
        </template>
        <template x-if="parsed?.notes">
            <span class="rounded-md bg-slate-100 px-2 py-0.5 text-slate-500">+ notes</span>
        </template>
    </div>

    <p x-show="expanded" x-cloak x-collapse class="mt-2 text-[11px] leading-5 text-slate-400">
        <code class="text-slate-500">{{ '@Project' }}</code> &middot;
        <code class="text-slate-500">!low !normal !high !urgent</code> &middot;
        <code class="text-slate-500">#flag #today #doing #done #backlog</code> &middot;
        dates like <code class="text-slate-500">tomorrow, friday, next week, in 3 days, 20/10</code><br>
        <kbd>Shift</kbd>+<kbd>Enter</kbd> for a new line — the first line is the title, the rest becomes notes.
    </p>
</form>
