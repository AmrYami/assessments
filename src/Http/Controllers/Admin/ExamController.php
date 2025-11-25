<?php

namespace Amryami\Assessments\Http\Controllers\Admin;

use Amryami\Assessments\Support\Controller;
use Amryami\Assessments\Domain\Models\{Exam, Topic, Question};
use Amryami\Assessments\Http\Requests\Admin\{StoreExamRequest, UpdateExamRequest};
use Amryami\Assessments\Services\ExamAssemblyService;
use Amryami\Assessments\Services\SchemaHashService;
use Amryami\Assessments\Support\ModelResolver;
use Illuminate\Support\Str;

class ExamController extends Controller
{
    protected function buildDifficultySplitSnapshot(array $data, ?Exam $existing): ?array
    {
        $mode = $data['assembly_mode'] ?? ($existing->assembly_mode ?? null);
        if (!in_array($mode, ['by_count','by_score'])) return $existing?->difficulty_split_json;
        $splits = $data['difficulty_split'] ?? [];
        $snap = [
            'mode' => $mode,
            'version' => (int) (($existing->difficulty_split_json['version'] ?? 0) + 1),
            'updated_at' => now()->toIso8601String(),
            'splits' => [
                'easy' => (int)($splits['easy'] ?? 0),
                'medium' => (int)($splits['medium'] ?? 0),
                'hard' => (int)($splits['hard'] ?? 0),
                'very_hard' => (int)($splits['very_hard'] ?? 0),
            ],
        ];
        if ($mode === 'by_count') $snap['question_count'] = (int)($data['question_count'] ?? 0);
        if ($mode === 'by_score') $snap['target_total_score'] = (int)($data['target_total_score'] ?? 0);
        return $snap;
    }

    protected function buildActivationFields(array $data, ?Exam $existing): array
    {
        $tokenLength = (int) config('assessments.activation.token_length', 40);
        $tokenLength = $tokenLength > 0 ? $tokenLength : 40;
        $expiresMinutes = (int) config('assessments.activation.expires_minutes', 0);

        $token = $data['activation_token'] ?? $existing?->activation_token ?? Str::random($tokenLength);
        $expiresAt = $data['activation_expires_at']
            ?? ($expiresMinutes > 0 ? now()->addMinutes($expiresMinutes) : null);
        $path = $data['activation_path'] ?? $existing?->activation_path ?? null;
        if (!$path && !empty($data['slug'])) {
            $prefix = trim((string) config('assessments.activation.prefix', 'assessments/activate'), '/');
            $path = $prefix !== '' ? "{$prefix}/{$data['slug']}" : $data['slug'];
        }

        return [
            'activation_path' => $path,
            'activation_token' => $token,
            'activation_expires_at' => $expiresAt,
        ];
    }
    public function index()
    {
        abort_unless(config('assessments.enabled') && config('assessments.admin_only'), 404);
        $page = 'Assessments — Exams';
        $items = Exam::orderByDesc('id')->paginate(20);
        return view('admin.assessments.exams.index', compact('page','items'));
    }

    public function create()
    {
        abort_unless(config('assessments.enabled') && config('assessments.admin_only'), 404);
        $page = 'Assessments — New Exam';
        $topics = Topic::orderBy('name')->get();
        $questions = Question::where('is_active',true)->orderByDesc('id')->limit(50)->get();
        $categoryModel = ModelResolver::category();
        $categories = $categoryModel::orderBy('name')->get();
        return view('admin.assessments.exams.create', compact('page','topics','questions','categories'));
    }

    public function store(StoreExamRequest $request)
    {
        $data = $request->validated();

        $activation = $this->buildActivationFields($data, null);

        $exam = Exam::create([
            'title' => $data['title'],
            'slug' => $data['slug'],
            'assembly_mode' => $data['assembly_mode'],
            'question_count' => $data['assembly_mode']==='by_count' ? ($data['question_count'] ?? null) : null,
            'target_total_score' => $data['assembly_mode']==='by_score' ? ($data['target_total_score'] ?? null) : null,
            'is_published' => (bool)($data['is_published'] ?? false),
            'status' => ($data['is_published'] ?? false) ? 'published' : 'draft',
            'shuffle_questions' => (bool)($data['shuffle_questions'] ?? false),
            'shuffle_options' => (bool)($data['shuffle_options'] ?? false),
            'show_explanations' => (bool)($data['show_explanations'] ?? false),
            'time_limit_seconds' => $data['time_limit_seconds'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'pass_type' => $data['pass_type'] ?? 'percent',
            'pass_value' => $data['pass_value'] ?? 70,
            'max_attempts' => $data['max_attempts'] ?? 1,
            'difficulty_split_json' => $this->buildDifficultySplitSnapshot($data, null),
            'activation_path' => $activation['activation_path'],
            'activation_token' => $activation['activation_token'],
            'activation_expires_at' => $activation['activation_expires_at'],
        ]);

        $exam->topics()->sync($data['topics'] ?? []);

        if ($exam->assembly_mode === 'manual') {
            $position = 1;
            foreach ($data['manual_questions'] ?? [] as $qid) {
                $exam->questions()->attach($qid, ['position' => $position++]);
            }
        }

        try { $hash = app(\Amryami\Assessments\Services\SchemaHashService::class)->computeForExam($exam); $exam->schema_hash = $hash; $exam->save(); } catch (\Throwable $e) {}
        return redirect()->route('dashboard.assessments.exams.edit', $exam)->with('success', 'Exam created');
    }

    public function edit(Exam $exam)
    {
        abort_unless(config('assessments.enabled') && config('assessments.admin_only'), 404);
        $page = 'Assessments — Edit Exam';
        $topics = Topic::orderBy('name')->get();
        $questions = Question::where('is_active',true)->orderByDesc('id')->limit(100)->get();
        $categoryModel = ModelResolver::category();
        $categories = $categoryModel::orderBy('name')->get();
        $exam->load(['topics','questions']);
        return view('admin.assessments.exams.edit', compact('page','exam','topics','questions','categories'));
    }

    public function update(UpdateExamRequest $request, Exam $exam)
    {
        $data = $request->validated();

        $activation = $this->buildActivationFields($data, $exam);

        $exam->update([
            'title' => $data['title'],
            'slug' => $data['slug'],
            'assembly_mode' => $data['assembly_mode'],
            'question_count' => $data['assembly_mode']==='by_count' ? ($data['question_count'] ?? null) : null,
            'target_total_score' => $data['assembly_mode']==='by_score' ? ($data['target_total_score'] ?? null) : null,
            'is_published' => (bool)($data['is_published'] ?? false),
            'status' => ($data['is_published'] ?? false) ? 'published' : $exam->status,
            'shuffle_questions' => (bool)($data['shuffle_questions'] ?? false),
            'shuffle_options' => (bool)($data['shuffle_options'] ?? false),
            'show_explanations' => (bool)($data['show_explanations'] ?? false),
            'time_limit_seconds' => $data['time_limit_seconds'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'pass_type' => $data['pass_type'] ?? $exam->pass_type,
            'pass_value' => $data['pass_value'] ?? $exam->pass_value,
            'max_attempts' => $data['max_attempts'] ?? $exam->max_attempts,
            'difficulty_split_json' => $this->buildDifficultySplitSnapshot($data, $exam),
            'activation_path' => $activation['activation_path'],
            'activation_token' => $activation['activation_token'],
            'activation_expires_at' => $activation['activation_expires_at'],
        ]);
        $exam->topics()->sync($data['topics'] ?? []);

        if ($exam->assembly_mode === 'manual') {
            $exam->questions()->detach();
            $position = 1;
            foreach ($data['manual_questions'] ?? [] as $qid) {
                $exam->questions()->attach($qid, ['position' => $position++]);
            }
        }

        try { $hash = app(\Amryami\Assessments\Services\SchemaHashService::class)->computeForExam($exam); $exam->schema_hash = $hash; $exam->save(); } catch (\Throwable $e) {}
        return redirect()->route('dashboard.assessments.exams.edit', $exam)->with('success', 'Exam updated');
    }

    public function publish(Exam $exam)
    {
        // Manual guard
        if ($exam->assembly_mode === 'manual' && $exam->questions()->count() < 1) {
            return back()->with('error', 'Cannot publish an empty manual exam.');
        }
        $assembly = app(\Amryami\Assessments\Services\ExamAssemblyService::class);
        $pool = $assembly->buildPool($exam);
        $strict = config('assessments.assembly.strict');
        $warning = null;

        if ($exam->assembly_mode === 'by_score') {
            $target = (int) ($exam->target_total_score ?? 0);
            if ($target > 0) {
                $split = ($exam->difficulty_split_json['mode'] ?? null) === 'by_score'
                    ? ($exam->difficulty_split_json['splits'] ?? null)
                    : null;
                try {
                    $selection = $assembly->sampleByScore($pool, $target, $split, !$strict);
                } catch (\RuntimeException $e) {
                    return back()->with('error', $e->getMessage());
                }
                if (empty($selection)) {
                    return back()->with('error', 'Unable to assemble exam from current pool.');
                }
                $actualScore = Question::whereIn('id', $selection)->sum('weight');
                if ($strict && $actualScore !== $target) {
                    return back()->with('error', 'Target is not achievable from current pool. Try adding questions with required weights or adjust target.');
                }
                if (!$strict && $actualScore < $target) {
                    $warning = "Tolerance applied: assembled score {$actualScore} of {$target}.";
                }
            }
        }

        if ($exam->assembly_mode === 'by_count') {
            $count = (int) ($exam->question_count ?? 0);
            if ($count > 0) {
                try {
                    $split = ($exam->difficulty_split_json['mode'] ?? null) === 'by_count'
                        ? ($exam->difficulty_split_json['splits'] ?? null)
                        : null;
                    $assembly->sampleByCount($pool, $count, crc32('publish-' . $exam->id), null, $split);
                } catch (\RuntimeException $e) {
                    return back()->with('error', $e->getMessage());
                }
            }
        }

        $exam->update(['is_published' => true, 'status' => 'published']);
        $response = back()->with('success', 'Exam published');
        if ($warning) {
            session()->flash('warning', $warning);
        }
        return $response;
    }

    public function unpublish(Exam $exam)
    {
        $exam->update(['is_published' => false, 'status' => 'draft']);
        return back()->with('success', 'Exam moved to draft');
    }

    public function archive(Exam $exam)
    {
        $exam->update(['status' => 'archived', 'is_published' => false]);
        return back()->with('success', 'Exam archived');
    }

    public function preview(Exam $exam)
    {
        // Admin-only preview placeholder. For manual mode, show ordered questions.
        abort_unless(config('assessments.enabled') && config('assessments.admin_only'), 404);
        $page = 'Assessments — Preview Exam';
        $exam->load('questions.options');
        return view('admin.assessments.exams.preview', compact('page','exam'));
    }

    public function coverage(Exam $exam)
    {
        abort_unless(config('assessments.enabled') && config('assessments.admin_only'), 404);
        $assembly = app(\Amryami\Assessments\Services\ExamAssemblyService::class);
        $pool = $assembly->buildPool($exam);
        $strict = config('assessments.assembly.strict');
        $allowTolerance = !$strict;

        $targetScore = (int)($exam->target_total_score ?? 0);
        $reachable = $assembly->coverage($pool, max($targetScore, 100));
        $achievable = $targetScore > 0 ? (bool)($reachable[$targetScore] ?? false) : true;
        $actualScore = 0;
        $toleranceUsed = false;
        $hints = [];

        if ($exam->assembly_mode === 'by_score' && $targetScore > 0) {
            $split = ($exam->difficulty_split_json['mode'] ?? null) === 'by_score'
                ? ($exam->difficulty_split_json['splits'] ?? null)
                : null;
            try {
                $selection = $assembly->sampleByScore($pool, $targetScore, $split, $allowTolerance);
            } catch (\RuntimeException $e) {
                $selection = [];
                $achievable = false;
                $hints[] = $e->getMessage();
            }
            if (!empty($selection)) {
                $actualScore = Question::whereIn('id', $selection)->sum('weight');
                if ($strict) {
                    $achievable = $actualScore === $targetScore;
                } else {
                    $achievable = $actualScore > 0;
                    if ($actualScore < $targetScore) {
                        $toleranceUsed = true;
                        $hints[] = "Tolerance applied: best achievable score is {$actualScore} of {$targetScore}.";
                    }
                }
            } else {
                $achievable = false;
            }
        }

        if ($exam->assembly_mode === 'by_count') {
            $countTarget = (int) ($exam->question_count ?? 0);
            $achievable = $countTarget > 0 && $pool->count() >= $countTarget;
        }

        $difficulty = $exam->difficulty_split_json['splits'] ?? null;
        if ($difficulty && is_array($difficulty)) {
            $mode = $exam->difficulty_split_json['mode'] ?? $exam->assembly_mode;
            $poolByDifficulty = $pool->groupBy('difficulty');

            if ($mode === 'by_count') {
                foreach ($difficulty as $diff => $required) {
                    $required = (int) $required;
                    if ($required <= 0) {
                        continue;
                    }
                    $available = ($poolByDifficulty[$diff] ?? collect())->count();
                    if ($available < $required) {
                        $achievable = false;
                        $hints[] = "Need {$required} {$diff} question(s); only {$available} available.";
                    }
                }
            } elseif ($mode === 'by_score') {
                foreach ($difficulty as $diff => $scoreRequired) {
                    $scoreRequired = (int) $scoreRequired;
                    if ($scoreRequired <= 0) {
                        continue;
                    }
                    $availableScore = ($poolByDifficulty[$diff] ?? collect())->sum('weight');
                    if ($availableScore < $scoreRequired) {
                        $achievable = false;
                        $hints[] = "Need {$scoreRequired} score from {$diff}; pool provides {$availableScore}.";
                    }
                }
            }
        }

        if (!$achievable) {
            $sum = $pool->sum('weight');
            if ($targetScore > 0 && $sum < $targetScore) {
                $hints[] = 'Total pool weight is less than target.';
            }
            if (empty($hints)) {
                $hints[] = 'Add questions or adjust weights/splits to reach the target.';
            }
        }
        return response()->json([
            'achievable' => $achievable,
            'hints' => $hints,
            'tolerance_used' => $toleranceUsed,
            'actual_score' => $actualScore,
            'target_score' => $targetScore,
        ]);
    }

    public function destroy(Exam $exam)
    {
        $exam->delete();
        return redirect()->route('dashboard.assessments.exams.index')->with('success', 'Exam deleted');
    }
}
