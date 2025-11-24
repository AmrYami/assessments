<?php

namespace Amryami\Assessments\Http\Controllers\Candidate;

use Amryami\Assessments\Support\Controller;
use Amryami\Assessments\Domain\Models\{
    AnswerKey,
    Attempt,
    AttemptTextAnswer,
    Exam,
    Question,
    QuestionResponsePart,
    ExamRequirement
};
use Amryami\Assessments\Http\Requests\Candidate\{SaveAnswersRequest, StartAttemptRequest};
use Amryami\Assessments\Http\Resources\{
    AttemptHeartbeatResource,
    AttemptResultResource,
    AttemptStartResource,
    AttemptSubmitResource
};
use Amryami\Assessments\Services\AnswerUsageService;
use Amryami\Assessments\Services\ExamAssemblyService;
use Amryami\Assessments\Support\ModelResolver;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttemptApiController extends Controller
{
    public function __construct(private ExamAssemblyService $assembly)
    {
    }

    public function start(Exam $exam, StartAttemptRequest $request)
    {
        abort_if(!(config('assessments.enabled') && !config('assessments.admin_only')), 404);
        $user = auth()->user();

        $count = Attempt::where('exam_id', $exam->id)
            ->where('user_id', $user->id)
            ->count();
        if ($exam->max_attempts && $count >= $exam->max_attempts) {
            return response()->json(['message' => 'Max attempts reached.'], 403);
        }

        $seed = $request->seed() ?? random_int(1, PHP_INT_MAX);
        $mode = $exam->assembly_mode;
        $pool = $this->assembly->buildPool($exam);

        try {
            if ($mode === 'manual') {
                $questionIds = $exam->questions()->pluck('assessment_questions.id')->all();
            } elseif ($mode === 'by_count') {
                $targetCount = (int) ($exam->question_count ?? 0);
                if ($targetCount <= 0) {
                    throw new \RuntimeException('Exam question count must be greater than zero.');
                }
                $split = null;
                if (($exam->difficulty_split_json['mode'] ?? null) === 'by_count') {
                    $split = $exam->difficulty_split_json['splits'] ?? null;
                }
                $questionIds = $this->assembly->sampleByCount(
                    $pool,
                    $targetCount,
                    $seed,
                    $user->id,
                    $split
                );
            } else {
                $targetScore = (int) ($exam->target_total_score ?? 0);
                if ($targetScore <= 0) {
                    throw new \RuntimeException('Exam target score must be greater than zero.');
                }
                $split = null;
                if (($exam->difficulty_split_json['mode'] ?? null) === 'by_score') {
                    $split = $exam->difficulty_split_json['splits'] ?? null;
                }
                $allowTolerance = !config('assessments.assembly.strict');
                $questionIds = $this->assembly->sampleByScore($pool, $targetScore, $split, $allowTolerance);
                if (empty($questionIds)) {
                    throw new \RuntimeException('Unable to assemble exam from current pool.');
                }
            }
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        if ($exam->shuffle_questions) {
            $questionIds = $this->assembly->seededShuffle($questionIds, $seed);
        }

        $now = CarbonImmutable::now();
        $expiresAt = $exam->time_limit_seconds ? $now->addSeconds($exam->time_limit_seconds) : null;

        $attempt = Attempt::create([
            'exam_id' => $exam->id,
            'user_id' => $user->id,
            'status' => 'in_progress',
            'started_at' => $now,
            'expires_at' => $expiresAt,
            'seed' => $seed,
            'frozen_question_ids' => $questionIds,
        ]);

        $req = ExamRequirement::where('user_id', $user->id)
            ->where('exam_id', $exam->id)
            ->first();
        if ($req) {
            $req->status = 'in_progress';
            $req->last_attempt_id = $attempt->id;
            $req->save();
        }

        $questions = Question::whereIn('id', $questionIds)
            ->with([
                'options' => fn($q) => $q->where('is_active', true)->orderBy('position'),
                'responseParts' => fn($q) => $q->orderBy('position'),
            ])
            ->get()
            ->keyBy('id');

        $payload = [];
        foreach ($questionIds as $qid) {
            $question = $questions[$qid];
            $entry = [
                'id' => $question->id,
                'text' => $question->text,
                'response_type' => $question->response_type,
                'selection_mode' => $question->selection_mode, // legacy field for existing UI JS
                'weight' => $question->weight,
                'note_enabled' => (bool) ($question->note_enabled ?? false),
                'note_required' => (bool) ($question->note_required ?? false),
                'note_hint' => $question->note_hint,
                'max_choices' => $question->response_type === 'multiple_choice' ? $question->max_choices : null,
            ];

            if ($this->isChoiceType($question->response_type)) {
                $activeOptions = $question->options
                    ->filter(fn($opt) => $opt->is_active)
                    ->sortBy('position')
                    ->values();

                $optionLookup = $activeOptions->map(function ($opt) {
                    return [
                        'id' => $opt->id,
                        'label' => $opt->label,
                        'key' => $opt->key,
                    ];
                })->keyBy('id');

                $order = $optionLookup->keys()->map(fn($id) => (int) $id)->all();
                if ($exam->shuffle_options) {
                    $order = $this->assembly->seededShuffle($order, $seed + $question->id);
                }
                $entry['options'] = collect($order)
                    ->map(fn($oid) => $optionLookup->get($oid))
                    ->filter()
                    ->values()
                    ->all();
            } else {
                $parts = $this->resolveResponseParts($question);
                $entry['response_parts'] = $parts
                    ->map(fn($part) => [
                        'key' => $part->key,
                        'label' => $part->label,
                        'input_type' => $part->input_type,
                        'required' => (bool) $part->required,
                        'validation_mode' => $part->validation_mode,
                        'validation_value' => $part->validation_value,
                    ])
                    ->values()
                    ->all();
            }

            $payload[] = $entry;
        }

        if (config('assessments.exposure_enabled')) {
            foreach ($questionIds as $qid) {
                DB::table('assessment_question_exposures')->updateOrInsert(
                    ['user_id' => $user->id, 'question_id' => $qid],
                    [
                        'seen_count' => DB::raw('COALESCE(seen_count,0)+1'),
                        'last_seen_at' => now(),
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }

        return new AttemptStartResource([
            'attempt_id' => $attempt->id,
            'expires_at' => optional($expiresAt)->toIso8601String(),
            'questions' => $payload,
        ]);
    }

    public function heartbeat(Attempt $attempt)
    {
        $this->authorizeAttempt($attempt);
        return new AttemptHeartbeatResource([
            'server_now' => now()->toIso8601String(),
            'expires_at' => optional($attempt->expires_at)->toIso8601String(),
            'status' => $attempt->status,
        ]);
    }

    public function saveAnswers(Attempt $attempt, SaveAnswersRequest $request)
    {
        $this->authorizeAttempt($attempt);

        $grace = (int) config('assessments.assembly.grace_seconds', 5);
        if ($attempt->expires_at && now()->greaterThan($attempt->expires_at->copy()->addSeconds($grace))) {
            return response()->json(['message' => 'Attempt expired'], 403);
        }

        $frozen = $attempt->frozen_question_ids ?? [];
        $questions = Question::whereIn('id', $frozen)
            ->with([
                'options' => fn($q) => $q->where('is_active', true)->orderBy('position'),
                'responseParts' => fn($q) => $q->orderBy('position'),
            ])
            ->get()
            ->keyBy('id');

        $answers = $request->answers();

        DB::transaction(function () use ($attempt, $answers, $frozen, $questions) {
            foreach ($answers as $row) {
                $qid = (int) ($row['question_id'] ?? 0);
                if (!in_array($qid, $frozen, true)) {
                    continue;
                }
                /** @var Question|null $question */
                $question = $questions->get($qid);
                if (!$question) {
                    continue;
                }

                $note = Arr::get($row, 'note', Arr::get($row, 'note_text'));
                $responseType = $question->response_type;

                if ($this->isChoiceType($responseType)) {
                    $optionIds = array_values(array_map('intval', Arr::get($row, 'option_ids', [])));
                    $validOptions = $question->options->pluck('id')->map(fn($id) => (int) $id)->all();
                    foreach ($optionIds as $optId) {
                        if (!in_array($optId, $validOptions, true)) {
                            throw ValidationException::withMessages([
                                'option_ids' => 'Invalid option selected.',
                            ]);
                        }
                    }
                    if ($question->response_type === 'multiple_choice' && $question->max_choices && count($optionIds) > $question->max_choices) {
                        throw ValidationException::withMessages([
                            'option_ids' => "You can select up to {$question->max_choices} options for this question.",
                        ]);
                    }
                    DB::table('assessment_attempt_answers')->updateOrInsert(
                        ['attempt_id' => $attempt->id, 'question_id' => $qid],
                        [
                            'option_ids' => json_encode($optionIds),
                            'input_values' => null,
                            'needs_review' => 0,
                            'note_text' => $this->storeNote($attempt->id, $qid, $question, $note),
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );
                    AttemptTextAnswer::where('attempt_id', $attempt->id)
                        ->where('question_id', $qid)
                        ->where('part_key', '!=', '__note__')
                        ->delete();
                } else {
                    $normalized = $this->normalizeTextParts($question, $row);
                    $this->validateTextResponses($question, $normalized);
                    foreach ($normalized as $key => $value) {
                        AttemptTextAnswer::updateOrCreate(
                            [
                                'attempt_id' => $attempt->id,
                                'question_id' => $qid,
                                'part_key' => $key,
                            ],
                            [
                                'text_value' => $value,
                                'is_valid' => null,
                                'score_awarded' => null,
                            ]
                        );
                    }
                    DB::table('assessment_attempt_answers')->updateOrInsert(
                        ['attempt_id' => $attempt->id, 'question_id' => $qid],
                        [
                            'option_ids' => json_encode([]),
                            'input_values' => json_encode($normalized),
                            'needs_review' => 1,
                            'note_text' => $this->storeNote($attempt->id, $qid, $question, $note),
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );
                }
            }
        });

        return response()->json(['saved' => true]);
    }

    public function submit(Attempt $attempt)
    {
        $this->authorizeAttempt($attempt);
        if ($attempt->status !== 'in_progress') {
            return response()->json(['message' => 'Already submitted'], 400);
        }

        $exam = Exam::findOrFail($attempt->exam_id);
        $showExplanations = (bool) $exam->show_explanations;

        $frozen = $attempt->frozen_question_ids ?? [];
        $questions = Question::whereIn('id', $frozen)
            ->with([
                'options' => fn($q) => $q->orderBy('position'),
                'responseParts' => fn($q) => $q->orderBy('position'),
            ])
            ->get()
            ->keyBy('id');

        $choiceAnswers = DB::table('assessment_attempt_answers')
            ->where('attempt_id', $attempt->id)
            ->get()
            ->keyBy('question_id');

        $textAnswers = AttemptTextAnswer::where('attempt_id', $attempt->id)
            ->get()
            ->groupBy('question_id');

        $totalPossible = 0;
        $score = 0;
        $needsReview = false;
        $details = [];

        foreach ($frozen as $qid) {
            /** @var Question|null $question */
            $question = $questions->get($qid);
            if (!$question) {
                continue;
            }
            $totalPossible += (int) $question->weight;
            $responseType = $question->response_type;
            $noteText = optional($choiceAnswers->get($qid))->note_text;

            if ($this->isChoiceType($responseType)) {
                $selected = collect(json_decode(optional($choiceAnswers->get($qid))->option_ids ?? '[]', true))
                    ->map(fn($v) => (int) $v)
                    ->all();
                if ($responseType === 'multiple_choice' && $question->max_choices && count($selected) > $question->max_choices) {
                    throw ValidationException::withMessages([
                        'option_ids' => "You can select up to {$question->max_choices} options for this question.",
                    ]);
                }
                $correctOptionIds = AnswerKey::where('question_id', $qid)
                    ->pluck('option_id')
                    ->map(fn($v) => (int) $v)
                    ->all();
                sort($selected);
                sort($correctOptionIds);
                $isCorrect = $responseType === 'single_choice'
                    ? ($selected === $correctOptionIds && count($selected) === 1)
                    : ($selected === $correctOptionIds);
                if ($isCorrect) {
                    $score += (int) $question->weight;
                }
                $details[] = [
                    'question_id' => $qid,
                    'response_type' => $responseType,
                    'selected' => $selected,
                    'correct' => $isCorrect,
                    'weight' => (int) $question->weight,
                    'note' => $noteText,
                    'explanation' => $showExplanations ? ($question->explanation ?? null) : null,
                ];
                if ($question->note_enabled && $question->note_required && empty(trim((string) $noteText))) {
                    $needsReview = true;
                }
            } else {
                $needsReview = true;
                $parts = $this->resolveResponseParts($question);
                $answersForQuestion = $textAnswers->get($qid) ?? collect();
                $map = $answersForQuestion->keyBy(fn(AttemptTextAnswer $a) => $a->part_key);
                $textParts = $parts->map(function ($part) use ($map) {
                    $value = optional($map->get($part->key))->text_value;
                    return [
                        'key' => $part->key,
                        'label' => $part->label,
                        'value' => $value,
                    ];
                })->all();
                $noteEntry = optional($answersForQuestion->firstWhere('part_key', '__note__'))->text_value ?? $noteText;
                $details[] = [
                    'question_id' => $qid,
                    'response_type' => $responseType,
                    'text_parts' => $textParts,
                    'weight' => (int) $question->weight,
                    'note' => $noteEntry,
                    'explanation' => $showExplanations ? ($question->explanation ?? null) : null,
                ];
            }
        }

        $percent = $totalPossible > 0
            ? (int) floor(($score / $totalPossible) * 100)
            : 0;
        $reviewStatus = $needsReview ? 'pending' : 'not_needed';
        $passed = $this->didPass($exam, $score, $percent);

        $attempt->update([
            'status' => 'submitted',
            'total_score' => $score,
            'score_auto' => $score,
            'score_manual' => 0,
            'review_status' => $reviewStatus,
            'percent' => $percent,
            'passed' => $passed,
            'result_json' => [
                'details' => $details,
                'total_possible' => $totalPossible,
            ],
        ]);

        $req = \Amryami\Assessments\Domain\Models\ExamRequirement::where('user_id', $attempt->user_id)
            ->where('exam_id', $attempt->exam_id)
            ->first();
        if ($req) {
            if ($needsReview && config('assessments.review_required_for_pass', false)) {
                $req->status = 'in_progress';
            } else {
                $req->status = $passed ? 'passed' : 'failed';
            }
            $req->attempts_used = ($req->attempts_used ?? 0) + 1;
            $req->last_attempt_id = $attempt->id;
            $req->save();

            if (!$passed) {
                $action = $req->fail_action ?? 'block_profile';
                if (in_array($action, ['block_profile_reject', 'allow_profile_reject', 'reject'])) {
                    $user = $this->resolveUserModel($attempt->user_id);
                    if ($user) {
                        $user->status = 'rejected';
                        $user->save();
                    }
                }
            }
        }

        try {
            $itemIds = DB::table('assessment_question_answer_links')
                ->whereIn('question_id', $frozen)
                ->pluck('answer_set_item_id')
                ->unique()
                ->values()
                ->all();
            if (!empty($itemIds)) {
                app(AnswerUsageService::class)->bumpAttempts($itemIds);
            }
        } catch (\Throwable $e) {
            // ignore usage tracking failures
        }

        $this->dispatchResultEmails($attempt, $score, $percent, $passed);

        return new AttemptSubmitResource([
            'attempt_id' => $attempt->id,
            'score' => $score,
            'score_auto' => $score,
            'score_manual' => 0,
            'percent' => $percent,
            'review_status' => $reviewStatus,
            'passed' => $passed,
            'details' => $details,
            'total_possible' => $totalPossible,
        ]);
    }

    public function result(Attempt $attempt)
    {
        $this->authorizeAttempt($attempt);
        if ($attempt->status !== 'submitted') {
            return response()->json(['message' => 'Not submitted yet'], 400);
        }

        $exam = Exam::find($attempt->exam_id);
        $details = $attempt->result_json['details'] ?? [];
        if (!$exam || !$exam->show_explanations) {
            $details = collect($details)->map(function ($detail) {
                unset($detail['explanation']);
                return $detail;
            })->all();
        }

        return new AttemptResultResource([
            'score' => $attempt->total_score,
            'percent' => $attempt->percent,
            'passed' => (bool) $attempt->passed,
            'total_possible' => $attempt->result_json['total_possible'] ?? null,
            'details' => $details,
            'review' => [
                'status' => $attempt->review_status,
                'score_auto' => (int) ($attempt->score_auto ?? 0),
                'score_manual' => (int) ($attempt->score_manual ?? 0),
                'notes' => $attempt->review_notes,
            ],
        ]);
    }

    protected function authorizeAttempt(Attempt $attempt): void
    {
        abort_if(!(config('assessments.enabled') && !config('assessments.admin_only')), 404);
        $user = auth()->user();
        if (!$user || $user->id !== $attempt->user_id) {
            abort(403);
        }
    }

    protected function didPass(Exam $exam, int $score, int $percent): bool
    {
        return $exam->pass_type === 'score'
            ? $score >= (int) $exam->pass_value
            : $percent >= (int) $exam->pass_value;
    }

    protected function isChoiceType(string $responseType): bool
    {
        return in_array($responseType, ['single_choice', 'multiple_choice'], true);
    }

    protected function resolveResponseParts(Question $question)
    {
        $parts = $question->responseParts;
        if ($parts->isNotEmpty()) {
            return $parts->map(function (QuestionResponsePart $part) {
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

    protected function normalizeTextParts(Question $question, array $row): array
    {
        $parts = $this->resolveResponseParts($question);
        $map = [];

        $providedParts = Arr::get($row, 'parts', []);
        if (is_array($providedParts)) {
            foreach ($providedParts as $partRow) {
                $key = Arr::get($partRow, 'key');
                if ($key !== null) {
                    $map[$key] = (string) (Arr::get($partRow, 'text', Arr::get($partRow, 'value', '')));
                }
            }
        }

        if (isset($row['text']) && $parts->count() === 1) {
            $firstKey = $parts->first()->key;
            $map[$firstKey] = (string) $row['text'];
        }

        $normalized = [];
        foreach ($parts as $part) {
            $normalized[$part->key] = $map[$part->key] ?? '';
        }

        return $normalized;
    }

    protected function validateTextResponses(Question $question, array $responses): void
    {
        $definitionParts = $this->resolveResponseParts($question);
        foreach ($definitionParts as $part) {
            $value = $responses[$part->key] ?? '';
            $trimmed = trim((string) $value);
            if ($part->required && $trimmed === '') {
                throw ValidationException::withMessages([
                    'parts' => "Response part {$part->label} is required.",
                ]);
            }
            if ($part->validation_mode === 'exact' && $trimmed !== '') {
                $expected = (string) $part->validation_value;
                if (mb_strtolower($trimmed) !== mb_strtolower($expected)) {
                    // keep as pending review, no error on autosave
                }
            }
            if ($part->validation_mode === 'regex' && $trimmed !== '') {
                $pattern = $part->validation_value;
                if ($pattern) {
                    $regex = '#' . str_replace('#', '\#', $pattern) . '#u';
                    if (@preg_match($regex, '') !== false && preg_match($regex, $trimmed) !== 1) {
                        // allow save but flag for review; we do not throw to allow correction later
                    }
                }
            }
        }
    }

    protected function storeNote(int $attemptId, int $questionId, Question $question, $note): ?string
    {
        $noteText = is_null($note) ? '' : trim((string) $note);
        if ($question->note_enabled) {
            if ($question->note_required && $noteText === '') {
                throw ValidationException::withMessages([
                    'note' => 'Note is required for this question.',
                ]);
            }
            if ($noteText !== '' || $question->note_required) {
                AttemptTextAnswer::updateOrCreate(
                    [
                        'attempt_id' => $attemptId,
                        'question_id' => $questionId,
                        'part_key' => '__note__',
                    ],
                    [
                        'text_value' => $noteText,
                        'is_valid' => null,
                        'score_awarded' => null,
                    ]
                );
            } else {
                AttemptTextAnswer::where('attempt_id', $attemptId)
                    ->where('question_id', $questionId)
                    ->where('part_key', '__note__')
                    ->delete();
            }
        }

        return $noteText !== '' ? $noteText : null;
    }

    protected function resolveUserModel(int $userId)
    {
        $class = ModelResolver::user();
        return $class::find($userId);
    }

    protected function dispatchResultEmails(Attempt $attempt, int $score, int $percent, bool $passed): void
    {
        try {
            $user = $this->resolveUserModel($attempt->user_id);
            $exam = Exam::find($attempt->exam_id);
            if (!$user || !$exam) {
                return;
            }

            if ($passed) {
                sendActionMail('exam_pass', $user, [
                    'body' => 'You have passed the exam: ' . $exam->title,
                    'exam_title' => $exam->title,
                    'score' => $score,
                    'percent' => $percent,
                ]);
            } else {
                sendActionMail('exam_fail', $user, [
                    'body' => 'Unfortunately, you did not pass the exam: ' . $exam->title . '. You may retake it if attempts remain.',
                    'exam_title' => $exam->title,
                    'score' => $score,
                    'percent' => $percent,
                ]);
            }
        } catch (\Throwable $e) {
            // ignore email dispatch errors
        }
    }
}
