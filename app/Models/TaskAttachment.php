<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class TaskAttachment extends Model
{
    protected $fillable = ['task_id', 'disk', 'path', 'name', 'mime_type', 'size'];

    protected function casts(): array
    {
        return ['size' => 'integer'];
    }

    protected $appends = ['url', 'download_url', 'is_image', 'size_label'];

    protected $hidden = ['disk', 'path'];

    protected static function booted(): void
    {
        // The row is gone once this fires; drop the stored file with it.
        static::deleted(function (TaskAttachment $attachment) {
            Storage::disk($attachment->disk)->delete($attachment->path);
        });
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function getUrlAttribute(): string
    {
        return route('tasks.attachments.show', [$this->task_id, $this->id]);
    }

    public function getDownloadUrlAttribute(): string
    {
        return route('tasks.attachments.show', [$this->task_id, $this->id, 'download' => 1]);
    }

    public function getIsImageAttribute(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }

    public function getSizeLabelAttribute(): string
    {
        $bytes = (float) $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];

        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return ($i === 0 ? (int) $bytes : round($bytes, 1)).' '.$units[$i];
    }
}
