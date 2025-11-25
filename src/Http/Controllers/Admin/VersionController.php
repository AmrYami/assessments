<?php

namespace Yami\Assessments\Http\Controllers\Admin;

use Yami\Assessments\Support\Controller;
use Yami\Assessments\Domain\Models\{Question, Exam};
use Illuminate\Http\Request;

class VersionController extends Controller
{
    public function questionHistory(Question $question)
    {
        abort_unless(config('assessments.enabled') && config('assessments.admin_only') && config('assessments.diff_and_history'), 404);
        abort_unless(auth()->user()?->can('exams.questions.edit'), 403);
        $page = 'Question History';
        $rootId = $question->origin_id ?: $question->id;
        $versions = Question::query()
            ->where(function ($q) use ($rootId) {
                $q->where('id', $rootId)->orWhere('origin_id', $rootId);
            })
            ->orderBy('version')
            ->get();
        // Simple placement counts
        $placements = \DB::table('assessment_question_placements')
            ->whereIn('question_id', $versions->pluck('id'))
            ->selectRaw('question_id, count(*) as cnt, sum(case when effective_at > now() then 1 else 0 end) as scheduled')
            ->groupBy('question_id')
            ->get()->keyBy('question_id');
        return view('admin.assessments.history.question', compact('page','versions','placements','rootId'));
    }

    public function examHistory(Exam $exam)
    {
        abort_unless(config('assessments.enabled') && config('assessments.admin_only') && config('assessments.diff_and_history'), 404);
        abort_unless(auth()->user()?->can('exams.exams.edit'), 403);
        $page = 'Exam History';
        $rootId = $exam->origin_id ?: $exam->id;
        $versions = Exam::query()
            ->where(function ($q) use ($rootId) {
                $q->where('id', $rootId)->orWhere('origin_id', $rootId);
            })
            ->orderBy('version')
            ->get();
        $placements = \DB::table('assessment_exam_placements')
            ->whereIn('exam_id', $versions->pluck('id'))
            ->selectRaw('exam_id, count(*) as cnt, sum(case when effective_at > now() then 1 else 0 end) as scheduled')
            ->groupBy('exam_id')
            ->get()->keyBy('exam_id');
        return view('admin.assessments.history.exam', compact('page','versions','placements','rootId'));
    }

    public function questionDiff(Request $request)
    {
        abort_unless(config('assessments.enabled') && config('assessments.admin_only') && config('assessments.diff_and_history'), 404);
        abort_unless(auth()->user()?->can('exams.questions.edit'), 403);
        $leftId = (int) $request->integer('left');
        $rightId = (int) $request->integer('right');
        $left = Question::findOrFail($leftId);
        $right = Question::findOrFail($rightId);
        $page = 'Question Diff';
        $diff = $this->diffQuestion($left, $right);
        return view('admin.assessments.history.question_diff', compact('page','left','right','diff'));
    }

    public function examDiff(Request $request)
    {
        abort_unless(config('assessments.enabled') && config('assessments.admin_only') && config('assessments.diff_and_history'), 404);
        abort_unless(auth()->user()?->can('exams.exams.edit'), 403);
        $leftId = (int) $request->integer('left');
        $rightId = (int) $request->integer('right');
        $left = Exam::findOrFail($leftId);
        $right = Exam::findOrFail($rightId);
        $page = 'Exam Diff';
        $diff = $this->diffExam($left, $right);
        return view('admin.assessments.history.exam_diff', compact('page','left','right','diff'));
    }

    protected function diffQuestion(Question $a, Question $b): array
    {
        $meta = [
            'text' => [$a->text, $b->text],
            'response_type' => [$a->response_type, $b->response_type],
            'weight' => [(int)$a->weight, (int)$b->weight],
            'difficulty' => [$a->difficulty, $b->difficulty],
            'note_enabled' => [(bool)$a->note_enabled, (bool)$b->note_enabled],
            'note_required' => [(bool)$a->note_required, (bool)$b->note_required],
            'note_hint' => [$a->note_hint, $b->note_hint],
        ];
        $optsA = $a->options()->get(['id','key','label'])->mapWithKeys(fn($o)=>[($o->key ?: ('#'.$o->id)) => $o->label])->all();
        $optsB = $b->options()->get(['id','key','label'])->mapWithKeys(fn($o)=>[($o->key ?: ('#'.$o->id)) => $o->label])->all();
        $added = array_diff_key($optsB, $optsA);
        $removed = array_diff_key($optsA, $optsB);
        $changed = [];
        foreach (array_intersect_key($optsA, $optsB) as $k => $v) if ($optsA[$k] !== $optsB[$k]) $changed[$k] = [$optsA[$k], $optsB[$k]];
        $partsA = $a->responseParts()->get()->mapWithKeys(fn($p)=>[$p->key=>[
            'label'=>$p->label,
            'input_type'=>$p->input_type,
            'required'=>(bool)$p->required,
            'validation_mode'=>$p->validation_mode,
            'validation_value'=>$p->validation_value,
        ]])->all();
        $partsB = $b->responseParts()->get()->mapWithKeys(fn($p)=>[$p->key=>[
            'label'=>$p->label,
            'input_type'=>$p->input_type,
            'required'=>(bool)$p->required,
            'validation_mode'=>$p->validation_mode,
            'validation_value'=>$p->validation_value,
        ]])->all();
        $partsAdded = array_diff_key($partsB, $partsA);
        $partsRemoved = array_diff_key($partsA, $partsB);
        $partsChanged = [];
        foreach (array_intersect_key($partsA, $partsB) as $k => $v) {
            if (json_encode($partsA[$k]) !== json_encode($partsB[$k])) {
                $partsChanged[$k] = [$partsA[$k], $partsB[$k]];
            }
        }
        return [
            'meta' => $meta,
            'options_added' => $added,
            'options_removed' => $removed,
            'options_changed' => $changed,
            'response_parts_added' => $partsAdded,
            'response_parts_removed' => $partsRemoved,
            'response_parts_changed' => $partsChanged,
        ];
    }

    protected function diffExam(Exam $a, Exam $b): array
    {
        $meta = [
            'title' => [$a->title, $b->title],
            'assembly_mode' => [$a->assembly_mode, $b->assembly_mode],
            'target_total_score' => [$a->target_total_score, $b->target_total_score],
            'question_count' => [$a->question_count, $b->question_count],
            'time_limit_seconds' => [$a->time_limit_seconds, $b->time_limit_seconds],
            'shuffle_questions' => [(bool)$a->shuffle_questions, (bool)$b->shuffle_questions],
            'shuffle_options' => [(bool)$a->shuffle_options, (bool)$b->shuffle_options],
        ];
        return compact('meta');
    }
}
