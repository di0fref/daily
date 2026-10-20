@props(['projects' => collect()])

<div x-data="{
        open: false,
        blank: { id: null, title: '', notes: '', status: 'backlog', priority: 'normal', flagged: false, due_date: '', project_id: null, parent_id: null, is_subtask: false },
        task: {},
        init() {
            this.task = Object.assign({}, this.blank);
        },
        show(detail) {
            this.task = Object.assign({}, this.blank, detail, {
                notes: detail.notes ?? '',
                due_date: detail.due_date ?? '',
                project_id: detail.project_id ?? null,
            });
            this.open = true;
            this.$nextTick(() => this.$refs.title?.focus());
        }
     }"
     @edit-task.window="show($event.detail)"
     @new-task.window="show(Object.assign({ id: null }, $event.detail))"
     @keydown.escape.window="open = false">

    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-slate-900/40 p-4 pt-20"
         x-transition.opacity>
        <div class="w-full max-w-lg rounded-2xl bg-white p-5 shadow-xl"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">

            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-base font-semibold text-slate-900" x-text="task.id ? 'Edit task' : (task.parent_id ? 'New subtask' : 'New task')"></h3>
                <button type="button" @click="open = false" class="rounded p-1 text-slate-400 hover:bg-slate-100">&times;</button>
            </div>

            <form method="POST" :action="task.id ? `/tasks/${task.id}` : '{{ route('tasks.store') }}'" class="space-y-3.5">
                @csrf
                <input type="hidden" name="_method" :value="task.id ? 'PUT' : 'POST'">
                <template x-if="!task.id && task.parent_id">
                    <input type="hidden" name="parent_id" :value="task.parent_id">
                </template>

                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Title</label>
                    <input type="text" name="title" x-ref="title" x-model="task.title" required
                           class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-indigo-400 focus:outline-none focus:ring-1 focus:ring-indigo-400">
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Notes</label>
                    <textarea name="notes" x-model="task.notes" rows="4"
                              class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-indigo-400 focus:outline-none focus:ring-1 focus:ring-indigo-400"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Status</label>
                        <select name="status" x-model="task.status"
                                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-indigo-400 focus:outline-none focus:ring-1 focus:ring-indigo-400">
                            @foreach (\App\Models\Task::STATUSES as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Priority</label>
                        <select name="priority" x-model="task.priority"
                                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-indigo-400 focus:outline-none focus:ring-1 focus:ring-indigo-400">
                            @foreach (\App\Models\Task::PRIORITIES as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Due date</label>
                        <input type="date" name="due_date" x-model="task.due_date"
                               class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-indigo-400 focus:outline-none focus:ring-1 focus:ring-indigo-400">
                    </div>
                    <div x-show="!task.is_subtask && !task.parent_id">
                        <label class="mb-1 block text-xs font-medium text-slate-600">Project</label>
                        <select name="project_id" x-model="task.project_id" :required="!task.id"
                                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-indigo-400 focus:outline-none focus:ring-1 focus:ring-indigo-400">
                            <option value="">Project…</option>
                            @foreach ($projects as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="hidden" name="flagged" value="0">
                    <input type="checkbox" name="flagged" value="1" x-model="task.flagged"
                           class="h-4 w-4 rounded border-slate-300 text-amber-500 focus:ring-amber-400">
                    Flag for daily focus
                </label>

                <div x-show="task.id" x-cloak x-data="taskAttachments()" x-effect="setTask(task.id)"
                     class="border-t border-slate-100 pt-3.5">
                    <div class="mb-1.5 flex items-center justify-between">
                        <label class="block text-xs font-medium text-slate-600">
                            Attachments
                            <span x-show="items.length" class="text-slate-400" x-text="`(${items.length})`"></span>
                        </label>
                        <button type="button" @click="pick()" :disabled="uploading"
                                class="text-xs font-medium text-indigo-600 hover:text-indigo-800 disabled:opacity-50">
                            <span x-text="uploading ? 'Uploading…' : 'Add files'"></span>
                        </button>
                    </div>

                    <input type="file" multiple x-ref="input" class="hidden" @change="fromInput($event)">

                    <div @dragover.prevent="dragging = true"
                         @dragleave.prevent="dragging = false"
                         @drop.prevent="fromDrop($event)"
                         @click="pick()"
                         class="cursor-pointer rounded-lg border border-dashed px-3 py-3 text-center text-xs transition"
                         :class="dragging ? 'border-indigo-400 bg-indigo-50 text-indigo-700' : 'border-slate-200 text-slate-500 hover:border-indigo-300 hover:bg-slate-50'">
                        Drop files here or click to browse <span class="text-slate-400">(max 10 files, 20 MB each)</span>
                    </div>

                    <p x-show="error" x-cloak class="mt-1.5 text-xs text-rose-600" x-text="error"></p>
                    <p x-show="loading" x-cloak class="mt-1.5 text-xs text-slate-400">Loading…</p>

                    <ul x-show="items.length" x-cloak class="mt-2 space-y-1">
                        <template x-for="file in items" :key="file.id">
                            <li class="flex items-center gap-2 rounded-lg border border-slate-200 px-2 py-1.5">
                                <template x-if="file.is_image">
                                    <img :src="file.url" :alt="file.name" class="h-8 w-8 shrink-0 rounded object-cover">
                                </template>
                                <template x-if="!file.is_image">
                                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded bg-slate-100 text-slate-500">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 13h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V20a2 2 0 01-2 2z"/>
                                        </svg>
                                    </span>
                                </template>
                                <a :href="file.url" target="_blank" rel="noopener"
                                   class="min-w-0 flex-1 truncate text-xs font-medium text-slate-700 hover:text-indigo-600"
                                   x-text="file.name"></a>
                                <span class="shrink-0 text-[11px] text-slate-400" x-text="file.size_label"></span>
                                <a :href="file.download_url" class="shrink-0 rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700" title="Download">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/>
                                    </svg>
                                </a>
                                <button type="button" @click="remove(file)"
                                        class="shrink-0 rounded p-1 text-slate-400 hover:bg-rose-50 hover:text-rose-600" title="Delete file">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </li>
                        </template>
                    </ul>
                </div>

                <div class="flex justify-end gap-2 pt-1">
                    <button type="button" @click="open = false"
                            class="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Cancel</button>
                    <button type="submit" x-text="task.id ? 'Save' : 'Create task'"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"></button>
                </div>
            </form>
        </div>
    </div>
</div>
