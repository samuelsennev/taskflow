<?php

namespace App\Services\Tasks;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Turns free-form text (plain lists, Markdown checklists, CSV-ish lines, Taskflow exports) into task attributes.
 *
 * Every non-indented line becomes a task. List markers and `[ ]` / `[x]` checkboxes are stripped, the
 * last date on the line (`YYYY-MM-DD` or `DD/MM/YYYY`) becomes the due date, indented lines below a task
 * become its description, and `#` headings name the project of the tasks that follow them.
 */
class TaskTextParser
{
    private const int TITLE_LIMIT = 255;

    private const int DESCRIPTION_LIMIT = 2000;

    /**
     * Parse the given file contents into a list of tasks.
     *
     * @return list<array{title: string, description: string|null, due_date: Carbon, is_completed: bool, heading: string|null}>
     */
    public function parse(string $contents): array
    {
        $tasks = [];
        $heading = null;
        $current = null;
        $pendingBlankLines = 0;

        foreach (Str::of($this->toUtf8($contents))->split('/\R/u') as $line) {
            if (trim($line) === '') {
                $pendingBlankLines++;

                continue;
            }

            if ($current !== null && preg_match('/^[ \t]/', $line)) {
                $current['description_lines'] = [
                    ...$current['description_lines'],
                    ...($current['description_lines'] === [] ? [] : array_fill(0, $pendingBlankLines, '')),
                    rtrim(preg_replace('/^(\t| {1,4})/', '', $line)),
                ];
                $pendingBlankLines = 0;

                continue;
            }

            $pendingBlankLines = 0;

            if ($current !== null) {
                $tasks[] = $this->finish($current);
                $current = null;
            }

            if (preg_match('/^\s*#{1,6}\s+(.+)$/u', $line, $matches)) {
                $heading = trim($matches[1]);

                continue;
            }

            $current = $this->parseTaskLine(trim($line), $heading);
        }

        if ($current !== null) {
            $tasks[] = $this->finish($current);
        }

        return array_values(array_filter($tasks, fn (array $task): bool => $task['title'] !== ''));
    }

    /**
     * Determine whether the contents look like text rather than a binary file.
     */
    public function isText(string $contents): bool
    {
        return ! str_contains($this->toUtf8($contents), "\0");
    }

    /**
     * Read the title, completion and due date out of a single task line.
     *
     * @return array{title: string, description_lines: list<string>, due_date: Carbon, is_completed: bool, heading: string|null}
     */
    private function parseTaskLine(string $line, ?string $heading): array
    {
        $line = preg_replace('/^(?:[-*+•–]|\d+[.)])\s+/u', '', $line);

        $isCompleted = false;

        if (preg_match('/^\[([ xX])\]\s*/', $line, $matches)) {
            $isCompleted = strtolower($matches[1]) === 'x';
            $line = substr($line, strlen($matches[0]));
        }

        $dueDate = null;

        preg_match_all('/[(\[]?\b(?:(\d{4})-(\d{2})-(\d{2})|(\d{1,2})\/(\d{1,2})\/(\d{4}))\b[)\]]?/', $line, $dateMatches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE | PREG_UNMATCHED_AS_NULL);

        foreach (array_reverse($dateMatches) as $matches) {
            [$year, $month, $day] = $matches[1][0] !== null
                ? [(int) $matches[1][0], (int) $matches[2][0], (int) $matches[3][0]]
                : [(int) $matches[6][0], (int) $matches[5][0], (int) $matches[4][0]];

            if (checkdate($month, $day, $year)) {
                $dueDate = Carbon::create($year, $month, $day)->startOfDay();
                $line = substr_replace($line, ' ', $matches[0][1], strlen($matches[0][0]));

                break;
            }
        }

        $title = preg_replace(['/\s{2,}/u', '/^[\s|,;:\-–—]+|[\s|,;:\-–—]+$/u'], [' ', ''], $line);

        return [
            'title' => mb_substr($title, 0, self::TITLE_LIMIT),
            'description_lines' => [],
            'due_date' => $dueDate ?? Carbon::today(),
            'is_completed' => $isCompleted,
            'heading' => $heading,
        ];
    }

    /**
     * Collapse the collected description lines into the final task shape.
     *
     * @param  array{title: string, description_lines: list<string>, due_date: Carbon, is_completed: bool, heading: string|null}  $task
     * @return array{title: string, description: string|null, due_date: Carbon, is_completed: bool, heading: string|null}
     */
    private function finish(array $task): array
    {
        $description = trim(implode("\n", $task['description_lines']));

        return [
            'title' => $task['title'],
            'description' => $description === '' ? null : mb_substr($description, 0, self::DESCRIPTION_LIMIT),
            'due_date' => $task['due_date'],
            'is_completed' => $task['is_completed'],
            'heading' => $task['heading'],
        ];
    }

    /**
     * Normalize UTF-16 and legacy Windows-1252 files to UTF-8 without a byte order mark.
     */
    private function toUtf8(string $contents): string
    {
        if (str_starts_with($contents, "\xFF\xFE")) {
            $contents = mb_convert_encoding(substr($contents, 2), 'UTF-8', 'UTF-16LE');
        } elseif (str_starts_with($contents, "\xFE\xFF")) {
            $contents = mb_convert_encoding(substr($contents, 2), 'UTF-8', 'UTF-16BE');
        } elseif (! mb_check_encoding($contents, 'UTF-8')) {
            $contents = mb_convert_encoding($contents, 'UTF-8', 'Windows-1252');
        }

        return preg_replace('/^\xEF\xBB\xBF/', '', $contents);
    }
}
