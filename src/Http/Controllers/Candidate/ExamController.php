<?php

namespace Amryami\Assessments\Http\Controllers\Candidate;

use Amryami\Assessments\Support\Controller;
use Amryami\Assessments\Domain\Models\{Exam, Question, Attempt, ExamRequirement};
use Amryami\Assessments\Http\Resources\ExamPreviewResource;
use Amryami\Assessments\Services\ExamAssemblyService;
use Amryami\Assessments\Support\ModelResolver;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    public function __construct(private ExamAssemblyService $assembly) {}

    public function index(Request $request)
    {
        abort_if(!(config('assessments.enabled') && !config('assessments.admin_only')), 404);
        abort_unless(optional(auth()->user())->can('exams.attempts.start'), 403);
        $user = auth()->user();
        $page = 'Exams';

        // Prefer placements mapping for user category; fallback to exams with no placements
        $published = Exam::query()->where(function($q){ $q->where('is_published', true)->orWhere('status','published'); });
        $preferred = collect();
        $fallback = collect();
        $now = now();
        $placed = \DB::table('assessment_exam_placements')->where(function($q) use ($now){ $q->whereNull('effective_at')->orWhere('effective_at','<=',$now); });
        $placedExamIds = $placed->pluck('exam_id')->unique()->values()->all();
        if (isset($user->hr_category_id)) {
            $preferredIds = (clone $placed)->where('category_id',$user->hr_category_id)->pluck('exam_id')->values()->all();
            if (!empty($preferredIds)) {
                $preferred = (clone $published)->whereIn('id',$preferredIds)->orderByDesc('id')->get();
            }
            // Fallback: exams without any placements at all, scoped by category_id as before
            $fallback = (clone $published)
                ->whereNotIn('id', $placedExamIds)
                ->where(function($q) use ($user){ $q->whereNull('category_id')->orWhere('category_id', $user->hr_category_id); })
                ->orderByDesc('id')
                ->get();
        } else {
            // No user category — just show published exams without placements, or any with null-category placements (rare)
            $fallback = (clone $published)->whereNotIn('id', $placedExamIds)->orderByDesc('id')->get();
        }
        $items = $preferred->merge($fallback)->unique('id')->values();
        // Hide exams where attempts exhausted
        $items = $items->filter(function($exam) use ($user){
            $used = Attempt::where('exam_id',$exam->id)->where('user_id',$user->id)->count();
            return $used < (int)($exam->max_attempts ?? 1);
        })->values();
        // Simple manual pagination not necessary here; list all

        $requirement = ExamRequirement::where('user_id', $user->id)->first();
        $entranceExam = $requirement ? Exam::find($requirement->exam_id) : null;
        $failCategory = null;
        if ($user && isset($user->category_id)) {
            $categoryModel = ModelResolver::category();
            $failCategory = $categoryModel::find($user->category_id);
        }

        return view('assessments::assessments.candidate.exams.index', compact('page','items','requirement','entranceExam','failCategory'));
    }

    public function preview(Exam $exam, Request $request)
    {
        abort_if(!(config('assessments.enabled') && !config('assessments.admin_only')), 404);
        abort_unless(optional(auth()->user())->can('exams.attempts.start'), 403);
        $user = auth()->user();
        // Gate visibility
        if (isset($user->hr_category_id) && $exam->category_id && $exam->category_id != $user->hr_category_id) {
            abort(403);
        }

        $mode = $exam->assembly_mode;
        $seed = random_int(1, PHP_INT_MAX);
        $pool = $this->assembly->buildPool($exam);
        try {
            if ($mode === 'manual') {
                $questionIds = $exam->questions()->pluck('assessment_questions.id')->all();
            } elseif ($mode === 'by_count') {
                $questionIds = $this->assembly->sampleByCount($pool, (int)($exam->question_count ?? 0), $seed, $user->id);
            } else { // by_score
                $questionIds = $this->assembly->subsetSumExact($pool, (int)($exam->target_total_score ?? 0));
            }
        } catch (\RuntimeException $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }

        if ($request->wantsJson()) {
            return new ExamPreviewResource([
                'seed' => $seed,
                'question_ids' => $questionIds,
                'mode' => $mode,
                'count' => count($questionIds),
                'total_score_preview' => $mode === 'by_score'
                    ? $exam->target_total_score
                    : array_sum(Question::whereIn('id', $questionIds)->pluck('weight')->all()),
            ]);
        }

        $page = 'Exam Preview — ' . $exam->title;
        $questions = Question::whereIn('id', $questionIds)
            ->with([
                'options' => fn($q) => $q->orderBy('position'),
                'responseParts' => fn($q) => $q->orderBy('position'),
            ])
            ->get();
        return view('assessments::assessments.candidate.exams.preview', compact('page','exam','questions','seed'));
    }

    public function results()
    {
        abort_if(!(config('assessments.enabled') && !config('assessments.admin_only')), 404);
        abort_unless(optional(auth()->user())->can('exams.attempts.view_result'), 403);
        $user = auth()->user();
        $page = 'My Results';
        $attempts = \Amryami\Assessments\Domain\Models\Attempt::where('user_id',$user->id)
            ->where('status','submitted')
            ->orderByDesc('id')->paginate(20);
        $exams = Exam::whereIn('id', $attempts->pluck('exam_id'))->get()->keyBy('id');
        $questionIds = collect($attempts->items())
            ->flatMap(function ($attempt) {
                $details = $attempt->result_json['details'] ?? [];
                return collect($details)->pluck('question_id');
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
        $questions = !empty($questionIds)
            ? Question::whereIn('id', $questionIds)->get(['id','text'])->keyBy('id')
            : collect();
        return view('assessments::assessments.candidate.exams.results', compact('page','attempts','exams','questions'));
    }
}
