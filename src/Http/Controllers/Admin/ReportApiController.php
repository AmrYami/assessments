<?php

namespace Amryami\Assessments\Http\Controllers\Admin;

use Amryami\Assessments\Support\Controller;
use Amryami\Assessments\Domain\Models\Exam;
use Amryami\Assessments\Services\ExamReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ReportApiController extends Controller
{
    public function __construct(private ExamReportService $reports)
    {
    }

    public function index(Request $request)
    {
        abort_unless(config('assessments.enabled') && config('assessments.admin_only'), 404);
        abort_unless(optional(auth()->user())->can('exams.reports.index'), 403);

        $timelineDays = max(1, (int) $request->integer('timeline_days', 14));
        $timelineStart = Carbon::now()->subDays($timelineDays)->startOfDay();
        $exams = $this->resolveExamFilter($request->input('exam'));

        $data = $this->reports
            ->build($exams, $timelineStart)
            ->map(fn(array $row) => $this->serializeRow($row))
            ->all();

        return response()->json([
            'generated_at' => now()->toIso8601String(),
            'data' => $data,
        ]);
    }

    public function show(Exam $exam, Request $request)
    {
        abort_unless(config('assessments.enabled') && config('assessments.admin_only'), 404);
        abort_unless(optional(auth()->user())->can('exams.reports.index'), 403);

        $timelineDays = max(1, (int) $request->integer('timeline_days', 14));
        $timelineStart = Carbon::now()->subDays($timelineDays)->startOfDay();

        $row = $this->reports->build(collect([$exam]), $timelineStart)->first();
        if (!$row) {
            return response()->json(['message' => 'Report not available'], 404);
        }

        return response()->json([
            'generated_at' => now()->toIso8601String(),
            'data' => $this->serializeRow($row),
        ]);
    }

    protected function resolveExamFilter(?string $examIdentifier): ?Collection
    {
        if (!$examIdentifier) {
            return null;
        }

        $exam = Exam::with('topics:id,name')
            ->where('slug', $examIdentifier)
            ->orWhere('id', is_numeric($examIdentifier) ? (int) $examIdentifier : 0)
            ->first();

        return $exam ? collect([$exam]) : collect();
    }

    protected function serializeRow(array $row): array
    {
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
    }
}
