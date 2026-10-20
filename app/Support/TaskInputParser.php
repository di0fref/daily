<?php

namespace App\Support;

use App\Models\Project;
use App\Models\Task;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Turns a line of free text into structured task data.
 *
 *   "Call Anna tomorrow !high @Website #flag"
 *     → title "Call Anna", due tomorrow, priority high, project Website, flagged
 *
 * Everything after the first line becomes the notes.
 */
class TaskInputParser
{
    private const PRIORITY_ALIASES = [
        'low' => 'low', 'l' => 'low',
        'normal' => 'normal', 'medium' => 'normal', 'med' => 'normal', 'n' => 'normal',
        'high' => 'high', 'h' => 'high',
        'urgent' => 'urgent', 'critical' => 'urgent', 'crit' => 'urgent', 'u' => 'urgent',
    ];

    private const STATUS_ALIASES = [
        'backlog' => Task::STATUS_BACKLOG,
        'later' => Task::STATUS_BACKLOG,
        'today' => Task::STATUS_TODAY,
        'next' => Task::STATUS_TODAY,
        'in-progress' => Task::STATUS_DOING,
        'inprogress' => Task::STATUS_DOING,
        'doing' => Task::STATUS_DOING,
        'wip' => Task::STATUS_DOING,
        'done' => Task::STATUS_DONE,
    ];

    private const FLAG_TAGS = ['flag', 'flagged', 'star'];

    private const WEEKDAYS = [
        'monday' => 1, 'mon' => 1,
        'tuesday' => 2, 'tues' => 2, 'tue' => 2,
        'wednesday' => 3, 'wed' => 3,
        'thursday' => 4, 'thurs' => 4, 'thur' => 4, 'thu' => 4,
        'friday' => 5, 'fri' => 5,
        'saturday' => 6, 'sat' => 6,
        'sunday' => 7, 'sun' => 7,
    ];

    private const MONTHS = [
        'january' => 1, 'jan' => 1,
        'february' => 2, 'feb' => 2,
        'march' => 3, 'mar' => 3,
        'april' => 4, 'apr' => 4,
        'may' => 5,
        'june' => 6, 'jun' => 6,
        'july' => 7, 'jul' => 7,
        'august' => 8, 'aug' => 8,
        'september' => 9, 'sept' => 9, 'sep' => 9,
        'october' => 10, 'oct' => 10,
        'november' => 11, 'nov' => 11,
        'december' => 12, 'dec' => 12,
    ];

    /** @var Collection<int, Project> */
    private Collection $projects;

    private CarbonImmutable $today;

    /**
     * @param  iterable<int, Project>|null  $projects  Defaults to every project.
     */
    public function __construct(?iterable $projects = null, ?CarbonImmutable $today = null)
    {
        $this->projects = Collection::make($projects ?? Project::query()->get());
        $this->today = $today ?? CarbonImmutable::today();
    }

    /**
     * @return array{title: string, notes: ?string, project_id: ?int, project_name: ?string,
     *               priority: ?string, status: ?string, flagged: ?bool, due_date: ?string}
     */
    public function parse(string $raw): array
    {
        [$line, $notes] = $this->splitTitleAndNotes($raw);
        $original = $line;

        $result = [
            'title' => $line,
            'notes' => $notes,
            'project_id' => null,
            'project_name' => null,
            'priority' => null,
            'status' => null,
            'flagged' => null,
            'due_date' => null,
        ];

        $line = $this->extractProject($line, $result);
        $line = $this->extractPriority($line, $result);
        $line = $this->extractTags($line, $result);
        $line = $this->extractDueDate($line, $result);

        $title = $this->tidy($line);
        $result['title'] = $title !== '' ? $title : $this->tidy($original);

        return $result;
    }

    /**
     * @return array{0: string, 1: ?string}
     */
    private function splitTitleAndNotes(string $raw): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($raw)) ?: [''];

        $title = '';
        $rest = [];

        foreach ($lines as $value) {
            if ($title === '') {
                if (trim($value) !== '') {
                    $title = trim($value);
                }

                continue;
            }

            $rest[] = $value;
        }

        $notes = trim(implode("\n", $rest));

        return [$title, $notes !== '' ? $notes : null];
    }

    /**
     * `@Website`, `@"Client work"`, `@Client work` or `@web` (prefix match).
     */
    private function extractProject(string $line, array &$result): string
    {
        if (! preg_match('/(?<![^\s])@(?:"([^"]+)"|(\S+))/u', $line, $m, PREG_OFFSET_CAPTURE)) {
            return $line;
        }

        $quoted = ($m[1][0] ?? '') !== '';
        $offset = $m[0][1];
        $length = strlen($m[0][0]);
        $needle = $quoted ? $m[1][0] : $m[2][0];

        if (! $quoted) {
            // Unquoted names may contain spaces: prefer the longest project name
            // that the text right after the "@" starts with.
            $tail = substr($line, $offset + 1);
            $best = null;

            foreach ($this->projects as $project) {
                $name = (string) $project->name;

                if ($name !== ''
                    && strcasecmp(substr($tail, 0, strlen($name)), $name) === 0
                    && strlen($name) > strlen((string) ($best?->name ?? ''))) {
                    $best = $project;
                }
            }

            if ($best !== null) {
                $needle = (string) $best->name;
                $length = strlen($needle) + 1;
            }
        }

        $project = $this->matchProject($needle);

        if ($project === null) {
            return $line;
        }

        $result['project_id'] = (int) $project->id;
        $result['project_name'] = (string) $project->name;

        return substr_replace($line, ' ', $offset, $length);
    }

    private function matchProject(string $needle): ?Project
    {
        $compact = fn (string $value) => (string) preg_replace('/[^a-z0-9]/', '', mb_strtolower($value));
        $target = $compact($needle);

        if ($target === '') {
            return null;
        }

        return $this->projects->first(fn (Project $p) => $compact((string) $p->name) === $target)
            ?? $this->projects->first(fn (Project $p) => str_starts_with($compact((string) $p->name), $target));
    }

    /**
     * `!high`, `!u`, or `!!` for urgent.
     */
    private function extractPriority(string $line, array &$result): string
    {
        if (preg_match('/(?<![^\s])!!(?![^\s])/u', $line, $m)) {
            $result['priority'] = 'urgent';

            return str_replace($m[0], ' ', $line);
        }

        $keys = implode('|', array_keys(self::PRIORITY_ALIASES));

        if (! preg_match('/(?<![^\s])!('.$keys.')(?![^\s])/iu', $line, $m)) {
            return $line;
        }

        $result['priority'] = self::PRIORITY_ALIASES[mb_strtolower($m[1])];

        return str_replace($m[0], ' ', $line);
    }

    /**
     * `#flag`, `#today`, `#doing`, `#done`, `#backlog`.
     */
    private function extractTags(string $line, array &$result): string
    {
        $keys = implode('|', [...array_keys(self::STATUS_ALIASES), ...self::FLAG_TAGS]);

        return preg_replace_callback(
            '/(?<![^\s])#('.$keys.')(?![^\s])/iu',
            function (array $m) use (&$result) {
                $tag = mb_strtolower($m[1]);

                if (in_array($tag, self::FLAG_TAGS, true)) {
                    $result['flagged'] = true;
                } else {
                    $result['status'] = self::STATUS_ALIASES[$tag];
                }

                return ' ';
            },
            $line
        ) ?? $line;
    }

    /**
     * Natural dates: today, tomorrow, friday, next week, in 3 days, 2026-10-20, 20/10, 5 oct.
     */
    private function extractDueDate(string $line, array &$result): string
    {
        $weekdays = implode('|', array_keys(self::WEEKDAYS));
        $months = implode('|', array_keys(self::MONTHS));

        $patterns = [
            '/(?<![^\s])(\d{4})-(\d{1,2})-(\d{1,2})(?![^\s])/u' => fn (array $m) => $this->makeDate((int) $m[1], (int) $m[2], (int) $m[3]),
            '/(?<![^\s])(\d{1,2})\/(\d{1,2})(?:\/(\d{2,4}))?(?![^\s])/u' => fn (array $m) => $this->makeDate(
                isset($m[3]) && $m[3] !== '' ? (int) (strlen($m[3]) === 2 ? '20'.$m[3] : $m[3]) : $this->today->year,
                (int) $m[2],
                (int) $m[1],
            ),
            '/(?<![^\s])(today|tonight)(?![^\s])/iu' => fn () => $this->today,
            '/(?<![^\s])(tomorrow|tmrw|tmr)(?![^\s])/iu' => fn () => $this->today->addDay(),
            '/(?<![^\s])next\s+week(?![^\s])/iu' => fn () => $this->nextWeekday(1, true),
            '/(?<![^\s])next\s+month(?![^\s])/iu' => fn () => $this->today->addMonth(),
            '/(?<![^\s])in\s+(\d{1,3})\s+(days?|weeks?|months?)(?![^\s])/iu' => fn (array $m) => match (rtrim(mb_strtolower($m[2]), 's')) {
                'day' => $this->today->addDays((int) $m[1]),
                'week' => $this->today->addWeeks((int) $m[1]),
                default => $this->today->addMonths((int) $m[1]),
            },
            '/(?<![^\s])next\s+('.$weekdays.')(?![^\s])/iu' => fn (array $m) => $this->nextWeekday(self::WEEKDAYS[mb_strtolower($m[1])], true),
            '/(?<![^\s])('.$weekdays.')(?![^\s])/iu' => fn (array $m) => $this->nextWeekday(self::WEEKDAYS[mb_strtolower($m[1])], false),
            '/(?<![^\s])('.$months.')\s+(\d{1,2})(?:st|nd|rd|th)?(?![^\s])/iu' => fn (array $m) => $this->monthDay(self::MONTHS[mb_strtolower($m[1])], (int) $m[2]),
            '/(?<![^\s])(\d{1,2})(?:st|nd|rd|th)?\s+('.$months.')(?![^\s])/iu' => fn (array $m) => $this->monthDay(self::MONTHS[mb_strtolower($m[2])], (int) $m[1]),
        ];

        foreach ($patterns as $pattern => $resolver) {
            if (! preg_match($pattern, $line, $m)) {
                continue;
            }

            $date = $resolver($m);

            if (! $date instanceof CarbonImmutable) {
                continue;
            }

            $result['due_date'] = $date->toDateString();

            return str_replace($m[0], ' ', $line);
        }

        return $line;
    }

    private function makeDate(int $year, int $month, int $day): ?CarbonImmutable
    {
        if (! checkdate($month, $day, $year)) {
            return null;
        }

        return $this->today->setDate($year, $month, $day);
    }

    /**
     * The coming occurrence of a weekday; today counts unless $skipCurrent.
     */
    private function nextWeekday(int $isoDay, bool $skipCurrent): CarbonImmutable
    {
        $diff = ($isoDay - $this->today->dayOfWeekIso + 7) % 7;

        if ($diff === 0 && $skipCurrent) {
            $diff = 7;
        }

        return $this->today->addDays($diff);
    }

    /**
     * A day in a named month, rolling into next year once the date has passed.
     */
    private function monthDay(int $month, int $day): ?CarbonImmutable
    {
        $date = $this->makeDate($this->today->year, $month, $day);

        if ($date === null) {
            return null;
        }

        return $date->lt($this->today) ? $date->addYear() : $date;
    }

    private function tidy(string $value): string
    {
        return trim((string) preg_replace('/\s{2,}/u', ' ', $value), " \t\n\r\0\x0B-–—:,");
    }
}
