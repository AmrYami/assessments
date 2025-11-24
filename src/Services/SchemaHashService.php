<?php

namespace Amryami\Assessments\Services;

use Amryami\Assessments\Domain\Models\{Question, Exam, QuestionOption, AnswerKey, QuestionAnswer, QuestionResponsePart};
use Illuminate\Support\Facades\DB;

class SchemaHashService
{
    public function computeForQuestion(Question $q): string
    {
        $meta = [
            'text' => (string)$q->text,
            'response_type' => (string)$q->response_type,
            'weight' => (int)$q->weight,
            'difficulty' => (string)$q->difficulty,
            'is_active' => (bool)$q->is_active,
            'note_enabled' => (bool)$q->note_enabled,
            'note_required' => (bool)$q->note_required,
            'note_hint' => (string) ($q->note_hint ?? ''),
        ];
        $options = QuestionOption::where('question_id', $q->id)->orderBy('position')->get(['id','label','key','is_active','answer_set_item_id']);
        $correctIds = AnswerKey::where('question_id', $q->id)->pluck('option_id')->map(fn($v)=>(int)$v)->all();
        $opts = [];
        foreach ($options as $o) {
            $opts[] = [
                'label' => (string) $o->label,
                'key' => (string) ($o->key ?? ''),
                'is_active' => (bool) $o->is_active,
                'is_correct' => in_array((int)$o->id, $correctIds, true),
                'answer_set_item_id' => (int) ($o->answer_set_item_id ?? 0),
            ];
        }
        $links = QuestionAnswer::where('question_id', $q->id)
            ->whereNull('deleted_at')
            ->orderBy('position')
            ->get(['answer_set_item_id','position','is_active','is_correct','label_override','value_override']);
        $linkData = $links->map(fn($lnk) => [
            'answer_set_item_id' => (int) $lnk->answer_set_item_id,
            'position' => (int) $lnk->position,
            'is_active' => (bool) $lnk->is_active,
            'is_correct' => (bool) $lnk->is_correct,
            'label_override' => $lnk->label_override,
            'value_override' => $lnk->value_override,
        ])->all();
        $parts = QuestionResponsePart::where('question_id', $q->id)
            ->whereNull('deleted_at')
            ->orderBy('position')
            ->get()
            ->map(fn($part) => [
                'key' => $part->key,
                'label' => $part->label,
                'input_type' => $part->input_type,
                'required' => (bool) $part->required,
                'validation_mode' => $part->validation_mode,
                'validation_value' => $part->validation_value,
                'weight_share' => $part->weight_share,
                'position' => (int) $part->position,
            ])
            ->all();
        $canonical = [
            'meta' => $meta,
            'options' => $opts,
            'answer_links' => $linkData,
            'response_parts' => $parts,
        ];
        $json = $this->canonicalJson($canonical);
        return hash('sha256', $json);
    }

    public function computeForExam(Exam $e): string
    {
        $meta = [
            'title' => (string)$e->title,
            'assembly_mode' => (string)$e->assembly_mode,
            'target_total_score' => (int)($e->target_total_score ?? 0),
            'question_count' => (int)($e->question_count ?? 0),
            'category_id' => (int)($e->category_id ?? 0),
            'time_limit_seconds' => (int)($e->time_limit_seconds ?? 0),
            'shuffle_questions' => (bool)$e->shuffle_questions,
            'shuffle_options' => (bool)$e->shuffle_options,
            'pass_type' => (string)$e->pass_type,
            'pass_value' => (int)$e->pass_value,
            'max_attempts' => (int)$e->max_attempts,
            'status' => (string)$e->status,
        ];
        $topicIds = DB::table('assessment_exam_topics')->where('exam_id',$e->id)->pluck('topic_id')->map(fn($v)=>(int)$v)->all(); sort($topicIds);
        $canonical = [ 'meta' => $meta, 'topics' => $topicIds ];
        $json = $this->canonicalJson($canonical);
        return hash('sha256', $json);
    }

    protected function canonicalJson($data): string
    {
        $sorted = $this->sortRecursive($data);
        return json_encode($sorted, JSON_UNESCAPED_UNICODE);
    }

    protected function sortRecursive($value)
    {
        if (is_array($value)) {
            // Distinguish assoc vs list
            $isAssoc = array_keys($value) !== range(0, count($value) - 1);
            if ($isAssoc) {
                ksort($value);
                foreach ($value as $k => $v) $value[$k] = $this->sortRecursive($v);
                return $value;
            }
            // List: map items
            return array_map(fn($v)=>$this->sortRecursive($v), $value);
        }
        return $value;
    }
}
