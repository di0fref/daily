<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Seeder;

class WorkBacklogSeeder extends Seeder
{
    public function run(): void
    {
        Task::query()->delete();
        Project::query()->delete();

        foreach ($this->blueprint() as $pIndex => $spec) {
            $project = Project::create([
                'name' => $spec['name'],
                'description' => $spec['description'] ?? null,
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
        $done = $spec['done'] ?? false;

        return Task::create([
            'project_id' => $projectId,
            'parent_id' => $parentId,
            'title' => $spec['title'],
            'notes' => $spec['notes'] ?? null,
            'status' => $done ? Task::STATUS_DONE : ($spec['status'] ?? Task::STATUS_BACKLOG),
            'priority' => $spec['priority'] ?? 'normal',
            'flagged' => $spec['flagged'] ?? false,
            'due_date' => $spec['due'] ?? null,
            'position' => $position,
            'completed_at' => $done ? now() : null,
        ]);
    }

    private function blueprint(): array
    {
        return [
            [
                'name' => 'Sustera',
                'color' => '#6366f1',
                'tasks' => [
                    ['title' => 'Kontrollera om order inte kollar flera gånger om misslyckats', 'done' => true],
                    ['title' => 'Ändra mailadressen till Nordic', 'done' => true],
                    [
                        'title' => '82250 kolla mot NGS',
                        'done' => true,
                        'subtasks' => [
                            ['title' => 'Den har fått ett ID = 147734, verkar vara skickat till NGS', 'done' => true],
                        ],
                    ],
                    [
                        'title' => 'Kontrollera avbokade försäkringar om de uppdateras från mäklarsystem',
                        'notes' => 'Detta gäller endast för att order skall gå till Klar att fakturera. Avbokade försäkringar låser status till Avvakta.',
                        'done' => true,
                    ],
                    [
                        'title' => 'Kolla vitec om såld',
                        'done' => true,
                        'subtasks' => [
                            ['title' => 'Det finns numera en status från mäklarsystemet, skall jag fixa det?', 'done' => true],
                            ['title' => 'Gäller också Dialog', 'done' => true],
                        ],
                    ],
                    [
                        'title' => 'Skicka manual om processerna till Christine',
                        'done' => true,
                        'subtasks' => [
                            ['title' => 'Skapat fil', 'done' => true],
                        ],
                    ],
                    [
                        'title' => 'Skicka list med ordrar i limbo',
                        'done' => true,
                        'subtasks' => [
                            ['title' => 'Skapat fil', 'done' => true],
                        ],
                    ],
                    ['title' => 'Estimate skickat', 'done' => true],
                    ['title' => 'Bytt mäklare på order?, skicka mail till Sustera', 'done' => true],
                    ['title' => 'Skapa ny/lookup mäklare vid ändring från mäklarsystemen', 'done' => true],
                ],
            ],
            [
                'name' => 'CUBSEC',
                'color' => '#0ea5e9',
                'tasks' => [
                    ['title' => 'Indexeringlathund - schemaläggare', 'done' => true],
                    ['title' => 'Utgångsnotis kontrakt', 'done' => true],
                ],
            ],
            [
                'name' => 'EXFO',
                'color' => '#f59e0b',
                'tasks' => [
                    ['title' => 'Upgrade 29/9', 'done' => true],
                    ['title' => 'Reindex ES', 'done' => true],
                    ['title' => 'Backup DB', 'done' => true],
                    ['title' => 'Make a snapshot of prod', 'done' => true],
                    ['title' => 'php bin/sugarcrm repair:sugarlogic', 'done' => true],
                    ['title' => 'Move/drag product between groups', 'done' => true],
                ],
            ],
            [
                'name' => 'NPS/Pharma',
                'color' => '#ec4899',
                'tasks' => [
                    ['title' => 'Exportdata kolla', 'done' => true],
                    ['title' => 'Import', 'status' => Task::STATUS_TODAY, 'priority' => 'high', 'flagged' => true],
                ],
            ],
            [
                'name' => 'GC',
                'color' => '#10b981',
                'tasks' => [
                    ['title' => 'Deploy new price list', 'done' => true],
                ],
            ],
            [
                'name' => 'TIPO',
                'color' => '#8b5cf6',
                'tasks' => [
                    ['title' => 'Tipo export files files/sugar_capex_tipo_export_20260924.csv', 'done' => true],
                ],
            ],
            [
                'name' => 'Internal',
                'color' => '#64748b',
                'tasks' => [
                    ['title' => 'Change initial start dates - Run on prod', 'done' => true],
                ],
            ],
        ];
    }
}
