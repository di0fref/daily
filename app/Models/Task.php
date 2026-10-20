<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use HasFactory;

    public const STATUS_BACKLOG = 'backlog';

    public const STATUS_TODAY = 'today';

    public const STATUS_DOING = 'doing';

    public const STATUS_DONE = 'done';

    public const STATUSES = [
        self::STATUS_BACKLOG => 'Backlog',
        self::STATUS_TODAY => 'Today',
        self::STATUS_DOING => 'In progress',
        self::STATUS_DONE => 'Done',
    ];

    public const PRIORITIES = [
        'low' => 'Low',
        'normal' => 'Normal',
        'high' => 'High',
        'urgent' => 'Urgent',
    ];

    protected $fillable = [
        'project_id', 'parent_id', 'title', 'notes', 'status',
        'priority', 'flagged', 'due_date', 'position', 'completed_at', 'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'flagged' => 'boolean',
            'due_date' => 'date',
            'completed_at' => 'datetime',
            'archived_at' => 'datetime',
            'position' => 'integer',
        ];
    }

    protected $appends = ['is_overdue', 'is_due_today'];

    protected static function booted(): void
    {
        // The database cascades the rows, but the stored files need the model
        // events, so delete children explicitly.
        static::deleting(function (Task $task) {
            $task->allSubtasks->each->delete();
            $task->attachments->each->delete();
        });
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class)->latest('id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_id');
    }

    public function subtasks(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_id')->whereNull('archived_at')->orderBy('position');
    }

    public function allSubtasks(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_id')->orderBy('position');
    }

    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeNotArchived(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->whereNotNull('archived_at');
    }

    public function getIsArchivedAttribute(): bool
    {
        return $this->archived_at !== null;
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', '!=', self::STATUS_DONE);
    }

    public function scopeDone(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DONE);
    }

    public function getIsDoneAttribute(): bool
    {
        return $this->status === self::STATUS_DONE;
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date !== null
            && ! $this->is_done
            && $this->due_date->isBefore(today());
    }

    public function getIsDueTodayAttribute(): bool
    {
        return $this->due_date !== null
            && ! $this->is_done
            && $this->due_date->isSameDay(today());
    }

    public function getSubtaskProgressAttribute(): int
    {
        $total = $this->subtasks->count();
        if ($total === 0) {
            return 0;
        }

        return (int) round($this->subtasks->where('status', self::STATUS_DONE)->count() / $total * 100);
    }
}
