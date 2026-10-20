<?php

namespace Tests\Unit;

use App\Models\Project;
use App\Support\TaskInputParser;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TaskInputParserTest extends TestCase
{
    private function parser(array $names = ['Website', 'Client work', 'Infiber']): TaskInputParser
    {
        $projects = [];

        foreach ($names as $index => $name) {
            $project = new Project(['name' => $name]);
            $project->id = $index + 1;
            $projects[] = $project;
        }

        // A Wednesday.
        return new TaskInputParser($projects, CarbonImmutable::parse('2026-10-14'));
    }

    public function test_it_extracts_every_token(): void
    {
        $result = $this->parser()->parse('Call Anna tomorrow !high @Website #flag');

        $this->assertSame('Call Anna', $result['title']);
        $this->assertSame('2026-10-15', $result['due_date']);
        $this->assertSame('high', $result['priority']);
        $this->assertSame(1, $result['project_id']);
        $this->assertSame('Website', $result['project_name']);
        $this->assertTrue($result['flagged']);
        $this->assertNull($result['notes']);
    }

    public function test_the_first_line_is_the_title_and_the_rest_are_notes(): void
    {
        $result = $this->parser()->parse("Write the report !urgent\nOutline first.\n\n- numbers\n- charts");

        $this->assertSame('Write the report', $result['title']);
        $this->assertSame("Outline first.\n\n- numbers\n- charts", $result['notes']);
        $this->assertSame('urgent', $result['priority']);
    }

    public function test_it_matches_multi_word_and_partial_project_names(): void
    {
        $this->assertSame(2, $this->parser()->parse('Invoice @Client work')['project_id']);
        $this->assertSame(2, $this->parser()->parse('Invoice @"Client work"')['project_id']);
        $this->assertSame(3, $this->parser()->parse('Deploy @infi')['project_id']);
        $this->assertNull($this->parser()->parse('Ping @nobody')['project_id']);
        $this->assertSame('Ping @nobody', $this->parser()->parse('Ping @nobody')['title']);
    }

    public function test_it_understands_status_and_flag_tags(): void
    {
        $result = $this->parser()->parse('Fix the build #doing #flag');

        $this->assertSame('Fix the build', $result['title']);
        $this->assertSame('doing', $result['status']);
        $this->assertTrue($result['flagged']);
    }

    #[DataProvider('dates')]
    public function test_it_parses_dates(string $input, ?string $expected, string $title): void
    {
        $result = $this->parser()->parse($input);

        $this->assertSame($expected, $result['due_date']);
        $this->assertSame($title, $result['title']);
    }

    public static function dates(): array
    {
        return [
            ['Standup today', '2026-10-14', 'Standup'],
            ['Standup tomorrow', '2026-10-15', 'Standup'],
            ['Ship it friday', '2026-10-16', 'Ship it'],
            ['Ship it wednesday', '2026-10-14', 'Ship it'],
            ['Ship it next wednesday', '2026-10-21', 'Ship it'],
            ['Retro next week', '2026-10-19', 'Retro'],
            ['Follow up in 3 days', '2026-10-17', 'Follow up'],
            ['Renew in 2 weeks', '2026-10-28', 'Renew'],
            ['Audit 2026-11-02', '2026-11-02', 'Audit'],
            ['Audit 20/10', '2026-10-20', 'Audit'],
            ['Audit 20/10/27', '2027-10-20', 'Audit'],
            ['Party 5 dec', '2026-12-05', 'Party'],
            ['Party dec 5', '2026-12-05', 'Party'],
            ['Party jan 5', '2027-01-05', 'Party'],
            ['Buy milk', null, 'Buy milk'],
            ['Audit 40/13', null, 'Audit 40/13'],
        ];
    }

    public function test_double_bang_means_urgent(): void
    {
        $result = $this->parser()->parse('Hotfix the login !!');

        $this->assertSame('Hotfix the login', $result['title']);
        $this->assertSame('urgent', $result['priority']);
    }

    public function test_it_keeps_the_original_line_when_only_tokens_were_typed(): void
    {
        $result = $this->parser()->parse('!high');

        $this->assertSame('!high', $result['title']);
    }

    public function test_it_leaves_plain_text_untouched(): void
    {
        $result = $this->parser()->parse('Email support@example.com about the invoice');

        $this->assertSame('Email support@example.com about the invoice', $result['title']);
        $this->assertNull($result['project_id']);
        $this->assertNull($result['priority']);
        $this->assertNull($result['due_date']);
    }
}
