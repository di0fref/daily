<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskAttachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TaskAttachmentController extends Controller
{
    private const MAX_KB = 20480;

    public function index(Task $task): JsonResponse
    {
        return response()->json(['attachments' => $task->attachments]);
    }

    public function store(Request $request, Task $task): JsonResponse
    {
        $request->validate([
            'files' => ['required', 'array', 'max:10'],
            'files.*' => ['file', 'max:'.self::MAX_KB],
        ], [
            'files.*.max' => 'Each file must be :max KB or smaller.',
        ]);

        $disk = config('filesystems.default');

        foreach ($request->file('files') as $file) {
            $path = $file->store('task-attachments/'.$task->id, $disk);

            $task->attachments()->create([
                'disk' => $disk,
                'path' => $path,
                'name' => $this->safeName($file->getClientOriginalName()),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        return response()->json(['attachments' => $task->attachments()->get()]);
    }

    public function show(Request $request, Task $task, TaskAttachment $attachment): StreamedResponse
    {
        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404);

        $disposition = $request->boolean('download') || ! $attachment->is_image
            ? ResponseHeaderBag::DISPOSITION_ATTACHMENT
            : ResponseHeaderBag::DISPOSITION_INLINE;

        return Storage::disk($attachment->disk)->response(
            $attachment->path,
            $attachment->name,
            ['Content-Type' => $attachment->mime_type ?: 'application/octet-stream'],
            $disposition
        );
    }

    public function destroy(Task $task, TaskAttachment $attachment): JsonResponse
    {
        $attachment->delete();

        return response()->json(['attachments' => $task->attachments()->get()]);
    }

    private function safeName(string $name): string
    {
        $name = str_replace(["\0", '/', '\\'], '-', $name);

        return mb_substr(trim($name) ?: 'file', 0, 255);
    }
}
