import Alpine from 'alpinejs';
import Sortable from 'sortablejs';
import collapse from '@alpinejs/collapse';

Alpine.plugin(collapse);
window.Alpine = Alpine;

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

function updateColumnCounts() {
    document.querySelectorAll('[data-column]').forEach((column) => {
        const status = column.dataset.column;
        const count = column.querySelectorAll('[data-task-id]').length;
        const badge = document.querySelector(`[data-count-for="${status}"]`);
        if (badge) badge.textContent = count;
        const empty = column.parentElement.querySelector('[data-empty]');
        if (empty) empty.classList.toggle('hidden', count > 0);
    });
}

function updateProjectProgress(project) {
    if (!project?.id) return;
    document.querySelectorAll(`[data-progress-for="${project.id}"]`).forEach((el) => {
        el.style.width = `${project.progress}%`;
    });
    document.querySelectorAll(`[data-progress-label="${project.id}"]`).forEach((el) => {
        el.textContent = `${project.done}/${project.total}`;
    });
    document.querySelectorAll(`[data-progress-pct="${project.id}"]`).forEach((el) => {
        el.textContent = `${project.progress}%`;
    });
}

function initKanban() {
    const columns = document.querySelectorAll('[data-column]');
    if (!columns.length) return;

    columns.forEach((column) => {
        Sortable.create(column, {
            group: 'kanban',
            animation: 150,
            ghostClass: 'kanban-ghost',
            dragClass: 'kanban-drag',
            draggable: '[data-task-id]',
            onEnd: async (evt) => {
                const card = evt.item;
                const target = evt.to;
                const status = target.dataset.column;
                const orderedIds = [...target.querySelectorAll('[data-task-id]')]
                    .map((el) => Number(el.dataset.taskId));

                card.dataset.status = status;
                card.classList.toggle('is-done', status === 'done');
                updateColumnCounts();

                try {
                    const res = await fetch(`/tasks/${card.dataset.taskId}/move`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf(),
                            Accept: 'application/json',
                        },
                        body: JSON.stringify({ status, ordered_ids: orderedIds }),
                    });
                    if (!res.ok) throw new Error(res.statusText);
                    const data = await res.json();
                    updateProjectProgress(data.project);
                    if (status === 'done' || card.dataset.hasSubtasks === '1') {
                        window.location.reload();
                    }
                } catch (e) {
                    console.error('Move failed', e);
                    window.location.reload();
                }
            },
        });
    });

    updateColumnCounts();
}

async function parseTaskInput(raw) {
    const res = await fetch('/tasks/parse', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf(),
            Accept: 'application/json',
        },
        body: JSON.stringify({ raw }),
    });

    if (!res.ok) throw new Error(res.statusText);

    return res.json();
}

/**
 * Light/dark theme toggle. The applied class is set by an inline script in the
 * layout head so there is no flash before Alpine boots.
 */
Alpine.data('themeToggle', () => ({
    dark: document.documentElement.classList.contains('dark'),

    toggle() {
        this.dark = !this.dark;
        document.documentElement.classList.toggle('dark', this.dark);
        localStorage.setItem('theme', this.dark ? 'dark' : 'light');
    },
}));

/**
 * Task card: the whole card opens the edit modal, except when the click landed
 * on a control, the subtask panel, or at the end of a drag.
 */
Alpine.data('taskCard', (task = {}) => ({
    task,
    open: false,
    downX: 0,
    downY: 0,

    trackPointer(event) {
        this.downX = event.clientX;
        this.downY = event.clientY;
    },

    edit() {
        window.dispatchEvent(new CustomEvent('edit-task', { detail: this.task }));
    },

    openEditor(event) {
        if (event.target.closest('a, button, input, select, textarea, label, form, [data-subtasks]')) return;
        if (window.getSelection()?.toString()) return;

        const dragged = Math.abs(event.clientX - this.downX) > 5 || Math.abs(event.clientY - this.downY) > 5;
        if (dragged) return;

        this.edit();
    },
}));

/**
 * Quick-add field: "Call Anna tomorrow !high @Website #flag" is parsed server
 * side, previewed as chips, and can be handed over to the full task modal.
 */
Alpine.data('quickAdd', (config = {}) => ({
    raw: '',
    parsed: null,
    expanded: false,
    projectId: config.projectId ? String(config.projectId) : '',
    timer: null,

    init() {
        this.$nextTick(() => this.autoGrow());
    },

    autoGrow() {
        const el = this.$refs.raw;
        if (!el) return;
        el.style.height = 'auto';
        el.style.height = `${Math.min(el.scrollHeight, 180)}px`;
    },

    schedule() {
        clearTimeout(this.timer);
        this.timer = setTimeout(() => this.preview(), 250);
    },

    async preview() {
        if (!this.raw.trim()) {
            this.parsed = null;
            return;
        }

        try {
            this.parsed = await parseTaskInput(this.raw);
            if (this.parsed.project_id) {
                this.projectId = String(this.parsed.project_id);
            }
        } catch (e) {
            console.error('Parse failed', e);
            this.parsed = null;
        }
    },

    submitOnEnter(event) {
        if (event.shiftKey) return;
        event.preventDefault();
        if (this.raw.trim()) this.$el.closest('form').requestSubmit();
    },

    async openFull() {
        let parsed = null;

        if (this.raw.trim()) {
            try {
                parsed = await parseTaskInput(this.raw);
            } catch (e) {
                console.error('Parse failed', e);
            }
        }

        window.dispatchEvent(new CustomEvent('new-task', {
            detail: {
                title: parsed?.title ?? '',
                notes: parsed?.notes ?? '',
                status: parsed?.status ?? config.status ?? 'backlog',
                priority: parsed?.priority ?? 'normal',
                flagged: parsed?.flagged ?? false,
                due_date: parsed?.due_date ?? '',
                project_id: parsed?.project_id ?? (this.projectId ? Number(this.projectId) : null),
                parent_id: config.parentId ?? null,
            },
        }));

        this.raw = '';
        this.parsed = null;
        this.$nextTick(() => this.autoGrow());
    },
}));

/**
 * Attachments panel inside the task modal: lists, uploads (click or drop) and
 * deletes files for one task.
 */
Alpine.data('taskAttachments', () => ({
    taskId: null,
    items: [],
    loading: false,
    uploading: false,
    dragging: false,
    error: '',

    setTask(id) {
        const next = id ?? null;
        if (next === this.taskId) return;

        this.taskId = next;
        this.items = [];
        this.error = '';
        this.dragging = false;
        if (next) this.load();
    },

    async load() {
        const id = this.taskId;
        this.loading = true;
        try {
            const res = await fetch(`/tasks/${id}/attachments`, {
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
            });
            if (!res.ok) throw new Error(res.statusText);
            const data = await res.json();
            if (this.taskId === id) this.apply(data.attachments);
        } catch (e) {
            console.error('Loading attachments failed', e);
            this.error = 'Could not load attachments.';
        } finally {
            this.loading = false;
        }
    },

    pick() {
        this.$refs.input?.click();
    },

    fromInput(event) {
        this.upload(event.target.files);
        event.target.value = '';
    },

    fromDrop(event) {
        this.dragging = false;
        this.upload(event.dataTransfer?.files);
    },

    async upload(fileList) {
        const files = [...(fileList ?? [])];
        if (!files.length || !this.taskId) return;

        const id = this.taskId;
        const body = new FormData();
        files.forEach((file) => body.append('files[]', file));

        this.uploading = true;
        this.error = '';
        try {
            const res = await fetch(`/tasks/${id}/attachments`, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
                body,
            });

            if (!res.ok) {
                const data = await res.json().catch(() => null);
                throw new Error(Object.values(data?.errors ?? {}).flat()[0] ?? 'Upload failed.');
            }

            const data = await res.json();
            if (this.taskId === id) this.apply(data.attachments);
        } catch (e) {
            console.error('Upload failed', e);
            this.error = e.message || 'Upload failed.';
        } finally {
            this.uploading = false;
        }
    },

    async remove(attachment) {
        if (!confirm(`Delete "${attachment.name}"?`)) return;

        const id = this.taskId;
        try {
            const res = await fetch(`/tasks/${id}/attachments/${attachment.id}`, {
                method: 'DELETE',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
            });
            if (!res.ok) throw new Error(res.statusText);
            const data = await res.json();
            if (this.taskId === id) this.apply(data.attachments);
        } catch (e) {
            console.error('Delete failed', e);
            this.error = 'Could not delete the file.';
        }
    },

    apply(attachments) {
        this.items = attachments ?? [];
        window.dispatchEvent(new CustomEvent('attachments-updated', {
            detail: { taskId: this.taskId, count: this.items.length },
        }));
    },
}));

window.addEventListener('attachments-updated', (event) => {
    const { taskId, count } = event.detail;
    document.querySelectorAll(`[data-attachments-for="${taskId}"]`).forEach((el) => {
        el.classList.toggle('hidden', !count);
        const label = el.querySelector('[data-attachments-count]');
        if (label) label.textContent = count;
    });
});

document.addEventListener('DOMContentLoaded', initKanban);

Alpine.start();
