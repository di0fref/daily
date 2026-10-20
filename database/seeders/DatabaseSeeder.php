<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Task::query()->delete();
        Project::query()->delete();

        foreach ($this->blueprint() as $pIndex => $spec) {
            $project = Project::create([
                'name' => $spec['name'],
                'description' => $spec['description'],
                'color' => $spec['color'],
                'position' => $pIndex,
            ]);

            foreach ($spec['tasks'] as $tIndex => $t) {
                $task = $this->makeTask($project->id, null, $tIndex, $t);

                foreach ($t['subtasks'] ?? [] as $sIndex => $s) {
                    $this->makeTask($project->id, $task->id, $sIndex, $s);
                }
            }
        }
    }

    private function makeTask(int $projectId, ?int $parentId, int $position, array $spec): Task
    {
        $status = $spec['status'] ?? Task::STATUS_BACKLOG;
        $due = isset($spec['due']) ? Carbon::today()->modify($spec['due']) : null;

        return Task::create([
            'project_id' => $projectId,
            'parent_id' => $parentId,
            'title' => $spec['title'],
            'notes' => $spec['notes'] ?? null,
            'status' => $status,
            'priority' => $spec['priority'] ?? 'normal',
            'flagged' => $spec['flagged'] ?? false,
            'due_date' => $due,
            'position' => $position,
            'completed_at' => $status === Task::STATUS_DONE
                ? Carbon::today()->addHours(rand(8, 17))
                : null,
        ]);
    }

    private function blueprint(): array
    {
        return [
            [
                'name' => 'Website redesign',
                'description' => 'New marketing site: design system, pages and launch.',
                'color' => '#6366f1',
                'tasks' => [
                    ['title' => 'Audit the current site', 'status' => 'done', 'due' => '-8 days'],
                    [
                        'title' => 'Build the design system',
                        'status' => 'doing',
                        'priority' => 'high',
                        'flagged' => true,
                        'due' => '+0 days',
                        'notes' => 'Tokens, components and documentation in Figma.',
                        'subtasks' => [
                            ['title' => 'Colour palette', 'status' => 'done'],
                            ['title' => 'Type scale', 'status' => 'done'],
                            ['title' => 'Component library', 'status' => 'doing', 'due' => '+0 days'],
                            ['title' => 'Dark mode variants', 'status' => 'backlog', 'priority' => 'low'],
                        ],
                    ],
                    [
                        'title' => 'Rewrite the pricing page copy',
                        'status' => 'today',
                        'priority' => 'urgent',
                        'due' => '-2 days',
                        'notes' => 'Legal still needs to approve the fine print.',
                    ],
                    ['title' => 'Set up analytics events', 'status' => 'backlog', 'priority' => 'normal', 'due' => '+6 days'],
                    ['title' => 'Accessibility pass (WCAG AA)', 'status' => 'backlog', 'priority' => 'high', 'flagged' => true],
                    ['title' => 'Pick a hosting provider', 'status' => 'done', 'due' => '-5 days'],
                ],
            ],
            [
                'name' => 'Q4 reporting',
                'description' => 'Close the books and ship the quarterly report.',
                'color' => '#0ea5e9',
                'tasks' => [
                    ['title' => 'Collect department numbers', 'status' => 'done', 'due' => '-4 days'],
                    [
                        'title' => 'Draft the executive summary',
                        'status' => 'today',
                        'priority' => 'high',
                        'due' => '+0 days',
                        'subtasks' => [
                            ['title' => 'Revenue highlights', 'status' => 'done'],
                            ['title' => 'Churn analysis', 'status' => 'today', 'due' => '+0 days'],
                            ['title' => 'Outlook for next quarter', 'status' => 'backlog'],
                        ],
                    ],
                    ['title' => 'Review with finance', 'status' => 'backlog', 'due' => '+3 days'],
                    ['title' => 'Publish to the board portal', 'status' => 'backlog', 'priority' => 'low', 'due' => '+9 days'],
                ],
            ],
            [
                'name' => 'Home & life',
                'description' => 'The admin that keeps everything else running.',
                'color' => '#10b981',
                'tasks' => [
                    ['title' => 'Renew the car insurance', 'status' => 'today', 'priority' => 'urgent', 'due' => '-1 days', 'flagged' => true],
                    ['title' => 'Book the dentist', 'status' => 'backlog', 'priority' => 'normal', 'due' => '+2 days'],
                    [
                        'title' => 'Plan the spring trip',
                        'status' => 'doing',
                        'priority' => 'low',
                        'subtasks' => [
                            ['title' => 'Compare flights', 'status' => 'done'],
                            ['title' => 'Shortlist hotels', 'status' => 'doing'],
                            ['title' => 'Book time off', 'status' => 'backlog', 'due' => '+14 days'],
                        ],
                    ],
                    ['title' => 'Replace the kitchen tap', 'status' => 'done', 'due' => '-10 days'],
                    ['title' => 'Water the plants', 'status' => 'done'],
                ],
            ],
        ];
    }
}
