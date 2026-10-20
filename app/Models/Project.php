<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'color', 'position', 'archived'];

    protected function casts(): array
    {
        return [
            'archived' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class)->whereNull('archived_at');
    }

    public function allTasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function archivedTasks(): HasMany
    {
        return $this->hasMany(Task::class)->whereNotNull('archived_at');
    }

    public function rootTasks(): HasMany
    {
        return $this->tasks()->whereNull('parent_id');
    }

    public function getTotalCountAttribute(): int
    {
        return $this->tasks->count();
    }

    public function getDoneCountAttribute(): int
    {
        return $this->tasks->where('status', Task::STATUS_DONE)->count();
    }

    public function getProgressAttribute(): int
    {
        $total = $this->total_count;

        return $total === 0 ? 0 : (int) round($this->done_count / $total * 100);
    }
}
