<?php

namespace Yami\Assessments\Console\Commands;

use Yami\Assessments\Domain\Models\Exam;
use Yami\Assessments\Services\ExamReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ExportExamReports extends Command
{
    protected $signature = 'assessments:reports
        {--exam= : Filter by exam id or slug}
        {--format=table : table, csv, or json}
        {--days=14 : Timeline window (in days) }
        {--path= : Output path for csv/json formats}';

    protected $description = 'Generate Assessments reporting snapshot (per exam metrics and timelines).';

    public function handle(ExamReportService $reports): int
    {
        $format = strtolower((string) $this->option('format'));
        if (!in_array($format, ['table', 'csv', 'json'], true)) {
            $this->error('Invalid format. Use table, csv, or json.');
            return self::FAILURE;
        }

        $days = max(1, (int) $this->option('days'));
        $timelineStart = Carbon::now()->subDays($days)->startOfDay();

        $exams = $this->resolveExamFilter();
        if ($exams === null && !$this->option('exam')) {
            $exams = Exam::with('topics:id,name')->orderBy('title')->get();
        }

        $rows = $reports->build($exams, $timelineStart);
        if ($rows->isEmpty()) {
            $this->warn('No exams matched the requested filters.');
            return self::SUCCESS;
        }

        return match ($format) {
            'table' => $this->renderTable($rows),
            'csv' => $this->exportCsv($rows),
            'json' => $this->exportJson($rows),
            default => self::FAILURE,
        };
    }

    protected function resolveExamFilter(): ?Collection
    {
        $examOption = $this->option('exam');
        if (!$examOption) {
            return null;
        }

        $exam = Exam::with('topics:id,name')
            ->where('slug', $examOption)
            ->orWhere('id', is_numeric($examOption) ? (int) $examOption : 0)
            ->first();

        if (!$exam) {
            $this->error("Exam not found for identifier [{$examOption}].");
            return collect();
        }

        return collect([$exam]);
    }

    protected function renderTable(Collection $rows): int
    {
        $this->table(
            ['Exam', 'Mode', 'Attempts', 'Pass Rate', 'Avg %', 'Pool Size', 'Timeline'],
            $rows->map(function (array $row) {
                $exam = $row['exam'];
                $metrics = $row['metrics'];
                $timeline = $metrics['timeline'] ?? [];
                $timelineSummary = !empty($timeline)
                    ? collect($timeline)->map(fn($t) => "{$t['date']}:{$t['attempts']}")->implode(', ')
                    : '—';

                return [
                    $exam->title,
                    ucfirst(str_replace('_', ' ', $exam->assembly_mode)),
                    $metrics['total_attempts'],
                    $metrics['pass_rate'] . '%',
                    $metrics['average_percent'] . '%',
                    $row['summary']['pool_size'],
                    $timelineSummary,
                ];
            })->all()
        );

        return self::SUCCESS;
    }

    protected function exportCsv(Collection $rows): int
    {
        $path = $this->option('path') ?: storage_path('app/assessments-report.csv');
        $handle = fopen($path, 'w');
        if (!$handle) {
            $this->error("Unable to open {$path} for writing.");
            return self::FAILURE;
        }

        fputcsv($handle, [
            'Exam', 'Mode', 'Target', 'Pool Size', 'Pool Score',
            'Easy Count', 'Easy Score',
            'Medium Count', 'Medium Score',
            'Hard Count', 'Hard Score',
            'Very Hard Count', 'Very Hard Score',
            'Total Attempts', 'Pass Rate (%)', 'Average Percent', 'Timeline JSON',
        ]);

        foreach ($rows as $row) {
            $exam = $row['exam'];
            $summary = $row['summary'];
            $metrics = $row['metrics'];
            $difficulty = $summary['difficulty'];

            fputcsv($handle, [
                $exam->title,
                $exam->assembly_mode,
                $exam->assembly_mode === 'by_count'
                    ? $exam->question_count
                    : ($exam->assembly_mode === 'by_score' ? $exam->target_total_score : null),
                $summary['pool_size'],
                $summary['pool_score'],
                $difficulty['easy']['count'] ?? 0,
                $difficulty['easy']['score'] ?? 0,
                $difficulty['medium']['count'] ?? 0,
                $difficulty['medium']['score'] ?? 0,
                $difficulty['hard']['count'] ?? 0,
                $difficulty['hard']['score'] ?? 0,
                $difficulty['very_hard']['count'] ?? 0,
                $difficulty['very_hard']['score'] ?? 0,
                $metrics['total_attempts'],
                $metrics['pass_rate'],
                $metrics['average_percent'],
                json_encode($metrics['timeline'] ?? []),
            ]);
        }

        fclose($handle);
        $this->info("CSV exported to {$path}");

        return self::SUCCESS;
    }

    protected function exportJson(Collection $rows): int
    {
        $path = $this->option('path') ?: storage_path('app/assessments-report.json');
        $payload = [
            'generated_at' => now()->toIso8601String(),
            'data' => $rows->map(fn($row) => [
                'exam' => [
                    'id' => $row['exam']->id,
                    'title' => $row['exam']->title,
                    'slug' => $row['exam']->slug,
                    'assembly_mode' => $row['exam']->assembly_mode,
                    'question_count' => $row['exam']->question_count,
                    'target_total_score' => $row['exam']->target_total_score,
                ],
                'summary' => $row['summary'],
                'metrics' => $row['metrics'],
            ])->all(),
        ];

        file_put_contents($path, json_encode($payload, JSON_PRETTY_PRINT));
        $this->info("JSON exported to {$path}");

        return self::SUCCESS;
    }
}
