<?php

namespace Yami\Assessments\Services;

use Yami\Assessments\Domain\Models\Attempt;
use Yami\Assessments\Domain\Models\Exam;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ExamReportService
{
    public function __construct(private ExamAssemblyService $assembly)
    {
    }

    /**
     * Build reporting rows for the provided exams (or all exams if omitted).
     */
    public function build(?Collection $exams = null, ?Carbon $timelineStart = null): Collection
    {
        if ($exams === null) {
            $exams = Exam::with('topics:id,name')->orderBy('title')->get();
        } else {
            if (!$exams instanceof EloquentCollection) {
                $exams = new EloquentCollection($exams->all());
            }
            $exams->loadMissing('topics:id,name');
        }
        if ($exams->isEmpty()) {
            return collect();
        }

        $examIds = $exams->pluck('id')->all();
        $timelineStart = $timelineStart ?? now()->subDays(14)->startOfDay();

        $attemptStats = $this->fetchAttemptStats($examIds);
        $timelines = $this->fetchAttemptTimeline($examIds, $timelineStart);

        return $exams->map(function (Exam $exam) use ($attemptStats, $timelines) {
            $summary = $this->assembly->summarizeExamPool($exam);
            $stats = $attemptStats[$exam->id] ?? [
                'total_attempts' => 0,
                'passed_attempts' => 0,
                'pass_rate' => 0.0,
                'average_percent' => 0,
            ];

            return [
                'exam' => $exam,
                'summary' => $summary,
                'metrics' => array_merge($stats, [
                    'timeline' => $timelines[$exam->id] ?? [],
                ]),
            ];
        });
    }

    protected function fetchAttemptStats(array $examIds): array
    {
        if (empty($examIds)) {
            return [];
        }

        return Attempt::selectRaw('exam_id, COUNT(*) as total_attempts, SUM(CASE WHEN passed = 1 THEN 1 ELSE 0 END) as passed_attempts, AVG(percent) as avg_percent')
            ->whereIn('exam_id', $examIds)
            ->where('status', 'submitted')
            ->groupBy('exam_id')
            ->get()
            ->mapWithKeys(function ($row) {
                $total = (int) $row->total_attempts;
                $passed = (int) $row->passed_attempts;
                $avgPercent = $row->avg_percent !== null ? (int) round($row->avg_percent) : 0;
                $passRate = $total > 0 ? round(($passed / $total) * 100, 2) : 0.0;

                return [
                    (int) $row->exam_id => [
                        'total_attempts' => $total,
                        'passed_attempts' => $passed,
                        'pass_rate' => $passRate,
                        'average_percent' => $avgPercent,
                    ],
                ];
            })
            ->all();
    }

    protected function fetchAttemptTimeline(array $examIds, Carbon $startDate): array
    {
        if (empty($examIds)) {
            return [];
        }

        $rows = Attempt::selectRaw('exam_id, DATE(started_at) as attempt_date, COUNT(*) as total_attempts, SUM(CASE WHEN passed = 1 THEN 1 ELSE 0 END) as passed_attempts')
            ->whereIn('exam_id', $examIds)
            ->whereDate('started_at', '>=', $startDate->toDateString())
            ->where('status', 'submitted')
            ->groupBy('exam_id', 'attempt_date')
            ->orderBy('attempt_date')
            ->get();

        $timeline = [];
        foreach ($rows as $row) {
            $timeline[(int) $row->exam_id][] = [
                'date' => $row->attempt_date,
                'attempts' => (int) $row->total_attempts,
                'passed' => (int) $row->passed_attempts,
            ];
        }

        return $timeline;
    }
}
