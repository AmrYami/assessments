<?php

namespace Yami\Assessments\Http\Controllers\Admin;

use Yami\Assessments\Support\Controller;
use Yami\Assessments\Domain\Models\{Question, Exam, Topic};
use Yami\Assessments\Http\Requests\Admin\{PreviewPropagationRequest, PropagateRequest};
use Yami\Assessments\Services\PropagationService;
use Yami\Assessments\Services\ExamAssemblyService;
use Yami\Assessments\Support\ModelResolver;
use Illuminate\Http\Request;

class PropagationApiController extends Controller
{
    public function __construct(private PropagationService $service) {}

    public function propagateQuestion(Question $question, PropagateRequest $request)
    {
        abort_unless(config('assessments.enabled') && config('assessments.admin_only'), 404);
        $data = $request->validated();
        $res = $this->service->propagateQuestion($question, $data['apply_to'] ?? [], $data['mode'], $data['effective_at'] ?? null);
        return response()->json($res);
    }

    public function propagateExam(Exam $exam, PropagateRequest $request)
    {
        abort_unless(config('assessments.enabled') && config('assessments.admin_only'), 404);
        $data = $request->validated();
        $res = $this->service->propagateExam($exam, $data['apply_to'] ?? [], $data['mode'], $data['effective_at'] ?? null);
        return response()->json($res);
    }

    public function previewQuestion(Question $question, PreviewPropagationRequest $request)
    {
        abort_unless(config('assessments.enabled') && config('assessments.admin_only'), 404);
        $data = $request->validated();
        $categoryClass = ModelResolver::category();
        $cats = $this->normalizeScope($data['apply_to']['categories'] ?? null, $categoryClass);
        $tops = $this->normalizeScope($data['apply_to']['topics'] ?? null, Topic::class);
        $summary = [
            'id' => $question->id,
            'family_root' => $question->origin_id ?: $question->id,
            'version' => (int) ($question->version ?? 1),
            'response_type' => $question->response_type,
            'weight' => (int)$question->weight,
            'note_enabled' => (bool)$question->note_enabled,
            'note_required' => (bool)$question->note_required,
            'note_hint' => $question->note_hint,
            'text_excerpt' => mb_substr($question->text, 0, 160),
            'options_count' => $question->options()->count(),
            'parts_count' => $question->responseParts()->count(),
        ];
        return response()->json([
            'apply_to' => [ 'categories' => $cats, 'topics' => $tops ],
            'combinations' => count($cats ?: [null]) * count($tops ?: [null]) - (empty($cats) && empty($tops) ? 1 : 0),
            'mode' => $data['mode'] ?? 'bump_placement',
            'effective_at' => $data['effective_at'] ?? null,
            'question_summary' => $summary,
        ]);
    }

    public function previewExam(Exam $exam, PreviewPropagationRequest $request)
    {
        abort_unless(config('assessments.enabled') && config('assessments.admin_only'), 404);
        $data = $request->validated();
        $categoryClass = ModelResolver::category();
        $cats = $this->normalizeScope($data['apply_to']['categories'] ?? null, $categoryClass);
        $tops = $this->normalizeScope($data['apply_to']['topics'] ?? null, Topic::class);
        $pool = app(ExamAssemblyService::class)->buildPool($exam);
        $target = (int)($exam->target_total_score ?? 0);
        $coverage = $target > 0 ? app(ExamAssemblyService::class)->coverage($pool, max($target, 100)) : [];
        $achievable = $target > 0 ? (bool)($coverage[$target] ?? false) : null;
        $hints = [];
        if ($target > 0 && $achievable === false) {
            $sum = $pool->sum('weight');
            if ($sum < $target) $hints[] = 'Total pool weight is less than target.';
            $hints[] = 'Add questions or adjust weights/splits to reach the target.';
        }
        return response()->json([
            'apply_to' => [ 'categories' => $cats, 'topics' => $tops ],
            'combinations' => count($cats ?: [null]) * count($tops ?: [null]) - (empty($cats) && empty($tops) ? 1 : 0),
            'mode' => $data['mode'] ?? 'bump_placement',
            'effective_at' => $data['effective_at'] ?? null,
            'exam_summary' => [
                'id' => $exam->id,
                'family_root' => $exam->origin_id ?: $exam->id,
                'version' => (int) ($exam->version ?? 1),
                'assembly_mode' => $exam->assembly_mode,
                'target_total_score' => $exam->target_total_score,
                'question_count' => $exam->question_count,
            ],
            'by_score' => $exam->assembly_mode === 'by_score' ? [ 'target' => $target, 'achievable' => $achievable, 'hints' => $hints ] : null,
        ]);
    }

    protected function normalizeScope($value, string $modelClass): array
    {
        if ($value === 'all') {
            return $modelClass::query()->pluck('id')->map(fn($v)=>(int)$v)->all();
        }
        if (is_array($value)) {
            return array_values(array_map('intval', $value));
        }
        return [];
    }
}
