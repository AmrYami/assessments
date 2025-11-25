<?php

namespace Amryami\Assessments\Http\Controllers\Admin;

use Amryami\Assessments\Support\Controller;
use Amryami\Assessments\Contracts\ReviewServiceInterface;
use Amryami\Assessments\Domain\Models\{Attempt, AttemptTextAnswer, Exam, Question};
use Amryami\Assessments\Http\Requests\Admin\UpdateReviewRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    public function __construct(private ReviewServiceInterface $reviews)
    {
    }

    public function index(Request $request)
    {
        abort_unless(config('assessments.enabled') && config('assessments.admin_only'), 404);
        abort_unless(optional(auth()->user())->can('exams.reviews.index'), 403);
        $page = 'Assessments — Reviews';
        $items = Attempt::whereIn('review_status', ['pending','in_review'])->orderByDesc('id')->paginate(20);
        $exams = Exam::whereIn('id', $items->pluck('exam_id'))->get()->keyBy('id');
        return view('assessments::admin.assessments.reviews.index', compact('page','items','exams'));
    }

    public function show(Attempt $attempt)
    {
        abort_unless(config('assessments.enabled') && config('assessments.admin_only'), 404);
        abort_unless(optional(auth()->user())->can('exams.reviews.index'), 403);
        $page = 'Review Attempt #' . $attempt->id;
        $attempt->load([]);
        $frozen = $attempt->frozen_question_ids ?? [];
        $questions = Question::whereIn('id', $frozen)
            ->with([
                'options' => fn($q) => $q->where('is_active', true)->orderBy('position'),
                'responseParts' => fn($q) => $q->orderBy('position'),
                'answerKeys',
            ])
            ->get()
            ->keyBy('id');

        $answers = DB::table('assessment_attempt_answers')
            ->where('attempt_id', $attempt->id)
            ->get()
            ->keyBy('question_id');

        $textAnswers = AttemptTextAnswer::where('attempt_id', $attempt->id)
            ->get()
            ->groupBy('question_id');

        $partsMap = $questions->mapWithKeys(function (Question $question) {
            return [$question->id => $this->resolveResponseParts($question)];
        });

        return view('assessments::admin.assessments.reviews.show', compact('page', 'attempt', 'questions', 'answers', 'textAnswers', 'partsMap'));
    }

    public function update(Attempt $attempt, UpdateReviewRequest $request)
    {
        abort_unless(optional(auth()->user())->can('exams.reviews.update'), 403);
        $data = $request->validated();

        $this->reviews->apply(
            $attempt,
            $data['items'],
            (bool) $request->boolean('finalize'),
            $data['review_notes'] ?? null,
            optional(auth()->user())->id
        );

        return back()->with('success', 'Review updated');
    }

    protected function resolveResponseParts(Question $question)
    {
        $parts = $question->responseParts;
        if ($parts->isNotEmpty()) {
            return $parts->map(function ($part) {
                return (object) [
                    'key' => $part->key,
                    'label' => $part->label,
                    'input_type' => $part->input_type,
                    'required' => (bool) $part->required,
                    'validation_mode' => $part->validation_mode,
                    'validation_value' => $part->validation_value,
                ];
            });
        }

        $legacy = DB::table('assessment_question_widgets')
            ->where('question_id', $question->id)
            ->whereNull('deleted_at')
            ->where('is_active', 1)
            ->orderBy('position')
            ->get();

        if ($legacy->isNotEmpty()) {
            return $legacy->map(function ($row) {
                return (object) [
                    'key' => $row->key,
                    'label' => $row->key,
                    'input_type' => $row->widget_type,
                    'required' => (bool) $row->required,
                    'validation_mode' => $row->regex ? 'regex' : 'none',
                    'validation_value' => $row->regex,
                ];
            });
        }

        return collect([(object) [
            'key' => 'text',
            'label' => 'Response',
            'input_type' => $question->response_type === 'textarea' ? 'textarea' : 'text',
            'required' => false,
            'validation_mode' => 'none',
            'validation_value' => null,
        ]]);
    }
}
