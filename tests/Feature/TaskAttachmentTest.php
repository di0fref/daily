<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TaskAttachmentTest extends TestCase
{
    use RefreshDatabase;

    private function task(): Task
    {
        $project = Project::create(['name' => 'Website', 'color' => '#4f46e5', 'position' => 0]);

        return Task::create([
            'project_id' => $project->id,
            'title' => 'Write the brief',
            'status' => 'today',
            'priority' => 'normal',
            'position' => 0,
        ]);
    }

    public function test_files_can_be_uploaded_listed_downloaded_and_deleted(): void
    {
        Storage::fake('local');
        $task = $this->task();

        $response = $this->post(route('tasks.attachments.store', $task), [
            'files' => [
                UploadedFile::fake()->image('shot.png'),
                UploadedFile::fake()->create('brief.pdf', 12, 'application/pdf'),
            ],
        ])->assertOk();

        $this->assertCount(2, $response->json('attachments'));
        $this->assertCount(2, $task->attachments()->get());

        $attachment = $task->attachments()->where('name', 'brief.pdf')->sole();
        Storage::disk('local')->assertExists($attachment->path);

        $this->getJson(route('tasks.attachments.index', $task))
            ->assertOk()
            ->assertJsonCount(2, 'attachments');

        $this->get(route('tasks.attachments.show', [$task, $attachment]))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=brief.pdf');

        $this->delete(route('tasks.attachments.destroy', [$task, $attachment]))
            ->assertOk()
            ->assertJsonCount(1, 'attachments');

        Storage::disk('local')->assertMissing($attachment->path);
    }

    public function test_oversized_uploads_are_rejected(): void
    {
        Storage::fake('local');
        $task = $this->task();

        $this->postJson(route('tasks.attachments.store', $task), [
            'files' => [UploadedFile::fake()->create('huge.zip', 20481)],
        ])->assertStatus(422)->assertJsonValidationErrors('files.0');

        $this->assertSame(0, $task->attachments()->count());
    }

    public function test_attachments_of_another_task_are_not_reachable(): void
    {
        Storage::fake('local');
        $task = $this->task();
        $other = $this->task();

        $this->post(route('tasks.attachments.store', $task), [
            'files' => [UploadedFile::fake()->create('brief.pdf', 10, 'application/pdf')],
        ])->assertOk();

        $attachment = $task->attachments()->sole();

        $this->get("/tasks/{$other->id}/attachments/{$attachment->id}")->assertNotFound();
        $this->delete("/tasks/{$other->id}/attachments/{$attachment->id}")->assertNotFound();
    }

    public function test_deleting_a_task_removes_its_files(): void
    {
        Storage::fake('local');
        $task = $this->task();

        $this->post(route('tasks.attachments.store', $task), [
            'files' => [UploadedFile::fake()->image('shot.png')],
        ])->assertOk();

        $path = $task->attachments()->sole()->path;

        $this->delete(route('tasks.destroy', $task))->assertRedirect();

        Storage::disk('local')->assertMissing($path);
        $this->assertDatabaseCount('task_attachments', 0);
    }
}
