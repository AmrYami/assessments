<?php

namespace Streaming\Assessments\Http\Controllers\Admin;

use Streaming\Assessments\Support\Controller;
use Streaming\Assessments\Domain\Models\Exam;
use Streaming\Assessments\Services\ExamReportService;

class ReportController extends Controller
{
    public function __construct(private ExamReportService $reports)
    {
    }

    public function index()
    {
        abort_unless(config('assessments.enabled') && config('assessments.admin_only'), 404);
        abort_unless(optional(auth()->user())->can('exams.reports.index'), 403);

        $page = 'Assessments — Reports';
        $exams = $this->reports->build();

        return view()->first([
            'admin.assessments.reports.index',
            'assessments::admin.assessments.reports.index',
        ], compact('page', 'exams'));
    }

    public function export()
    {
        abort_unless(config('assessments.enabled') && config('assessments.admin_only'), 404);
        abort_unless(optional(auth()->user())->can('exams.reports.index'), 403);

        $rows = $this->reports->build();

        $callback = function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'Exam',
                'Mode',
                'Target',
                'Pool Size',
                'Pool Score',
                'Easy Count',
                'Easy Score',
                'Medium Count',
                'Medium Score',
                'Hard Count',
                'Hard Score',
                'Very Hard Count',
                'Very Hard Score',
                'Total Attempts',
                'Pass Rate (%)',
                'Average Percent (%)',
                'Tolerance Used',
            ]);

            foreach ($rows as $row) {
                /** @var Exam $exam */
                $exam = $row['exam'];
                $summary = $row['summary'];
                $metrics = $row['metrics'];
                $difficulty = $summary['difficulty'];
                $coverage = $summary['coverage'];

                fputcsv($out, [
                    $exam->title,
                    $exam->assembly_mode,
                    $exam->assembly_mode === 'by_count'
                        ? $exam->question_count
                        : ($exam->assembly_mode === 'by_score' ? $exam->target_total_score : null),
                    $summary['pool_size'],
                    $summary['pool_score'],
                    $difficulty['easy']['count'],
                    $difficulty['easy']['score'],
                    $difficulty['medium']['count'],
                    $difficulty['medium']['score'],
                    $difficulty['hard']['count'],
                    $difficulty['hard']['score'],
                    $difficulty['very_hard']['count'],
                    $difficulty['very_hard']['score'],
                    $metrics['total_attempts'],
                    $metrics['pass_rate'],
                    $metrics['average_percent'],
                    $coverage['tolerance'] ?? false ? 'yes' : 'no',
                ]);
            }
            fclose($out);
        };

        return response()->streamDownload($callback, 'assessments-report.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function exportJson()
    {
        abort_unless(config('assessments.enabled') && config('assessments.admin_only'), 404);
        abort_unless(optional(auth()->user())->can('exams.reports.index'), 403);

        $rows = $this->reports->build()->map(function (array $row) {
            /** @var Exam $exam */
            $exam = $row['exam'];

            return [
                'exam' => [
                    'id' => $exam->id,
                    'title' => $exam->title,
                    'slug' => $exam->slug,
                    'assembly_mode' => $exam->assembly_mode,
                    'question_count' => $exam->question_count,
                    'target_total_score' => $exam->target_total_score,
                    'is_published' => (bool) $exam->is_published,
                    'status' => $exam->status,
                    'category_id' => $exam->category_id,
                    'topics' => $exam->topics->map(fn($topic) => [
                        'id' => $topic->id,
                        'name' => $topic->name,
                    ])->all(),
                ],
                'summary' => $row['summary'],
                'metrics' => $row['metrics'],
            ];
        })->all();

        return response()->json([
            'data' => $rows,
            'generated_at' => now()->toIso8601String(),
        ]);
    }

}
