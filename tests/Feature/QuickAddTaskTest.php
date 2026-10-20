<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuickAddTaskTest extends TestCase
{
    use RefreshDatabase;

    private function project(string $name = 'Website'): Project
    {
        return Project::create(['name' => $name, 'color' => '#4f46e5', 'position' => 0]);
    }

    public function test_quick_add_syntax_fills_the_task_fields(): void
    {
        $inbox = $this->project('Inbox');
        $website = $this->project('Website');

        $this->post(route('tasks.store'), [
            'raw' => "Call Anna tomorrow !high @Website #flag\nRing the office number.",
            'status' => 'backlog',
            'project_id' => $inbox->id,
        ])->assertRedirect();

        $task = Task::sole();

        $this->assertSame('Call Anna', $task->title);
        $this->assertSame('Ring the office number.', $task->notes);
        $this->assertSame('high', $task->priority);
        $this->assertSame($website->id, $task->project_id);
        $this->assertTrue($task->flagged);
        $this->assertTrue($task->due_date->isTomorrow());
        $this->assertSame('backlog', $task->status);
    }

    public function test_a_parsed_status_wins_over_the_form_context(): void
    {
        $project = $this->project();

        $this->post(route('tasks.store'), [
            'raw' => 'Review the PR #doing',
            'status' => 'backlog',
            'project_id' => $project->id,
        ])->assertRedirect();

        $this->assertSame('doing', Task::sole()->status);
    }

    public function test_plain_input_still_creates_a_simple_task(): void
    {
        $project = $this->project();

        $this->post(route('tasks.store'), [
            'raw' => 'Buy milk',
            'status' => 'today',
            'project_id' => $project->id,
        ])->assertRedirect();

        $task = Task::sole();

        $this->assertSame('Buy milk', $task->title);
        $this->assertSame('today', $task->status);
        $this->assertSame('normal', $task->priority);
        $this->assertNull($task->due_date);
        $this->assertFalse($task->flagged);
    }

    public function test_the_full_form_still_posts_explicit_fields(): void
    {
        $project = $this->project();

        $this->post(route('tasks.store'), [
            'title' => 'Tomorrow is a word in a title',
            'notes' => 'Some notes',
            'status' => 'today',
            'priority' => 'urgent',
            'due_date' => '2026-12-24',
            'flagged' => '1',
            'project_id' => $project->id,
        ])->assertRedirect();

        $task = Task::sole();

        $this->assertSame('Tomorrow is a word in a title', $task->title);
        $this->assertSame('2026-12-24', $task->due_date->toDateString());
        $this->assertSame('urgent', $task->priority);
        $this->assertTrue($task->flagged);
    }

    public function test_a_subtask_inherits_the_parent_project_without_an_explicit_one(): void
    {
        $project = $this->project();
        $parent = Task::create([
            'project_id' => $project->id,
            'title' => 'Parent',
            'status' => 'backlog',
            'priority' => 'normal',
            'position' => 0,
        ]);

        $this->post(route('tasks.store'), [
            'raw' => 'Draft copy !high',
            'parent_id' => $parent->id,
        ])->assertRedirect();

        $child = Task::where('parent_id', $parent->id)->sole();

        $this->assertSame('Draft copy', $child->title);
        $this->assertSame($project->id, $child->project_id);
        $this->assertSame('high', $child->priority);
    }

    public function test_the_parse_endpoint_returns_a_preview(): void
    {
        $project = $this->project();

        $this->postJson(route('tasks.parse'), ['raw' => 'Ship it today !urgent @Website'])
            ->assertOk()
            ->assertJson([
                'title' => 'Ship it',
                'project_id' => $project->id,
                'project_name' => 'Website',
                'priority' => 'urgent',
                'priority_label' => 'Urgent',
                'due_date' => today()->toDateString(),
                'due_label' => 'Today',
            ]);
    }

    public function test_an_empty_quick_add_is_rejected(): void
    {
        $project = $this->project();

        $this->post(route('tasks.store'), ['raw' => '', 'project_id' => $project->id])
            ->assertSessionHasErrors('title');

        $this->assertSame(0, Task::count());
    }
}
