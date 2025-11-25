<?php

namespace Amryami\Assessments\Domain\Models;

use Amryami\Assessments\Support\BaseModel;

class Exam extends BaseModel
{
    protected $table = 'assessment_exams';

    protected $fillable = [
        'title',
        'slug',
        'category_id',
        'assembly_mode',
        'target_total_score',
        'question_count',
        'is_published',
        'status',
        'time_limit_seconds',
        'shuffle_questions',
        'shuffle_options',
        'show_explanations',
        'pass_type',
        'pass_value',
        'max_attempts',
        'difficulty_split_json',
        'origin_id',
        'version',
        'activation_path',
        'activation_token',
        'activation_expires_at',
        'activation_used_at',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'target_total_score' => 'integer',
        'question_count' => 'integer',
        'is_published' => 'boolean',
        'time_limit_seconds' => 'integer',
        'shuffle_questions' => 'boolean',
        'shuffle_options' => 'boolean',
        'show_explanations' => 'boolean',
        'pass_value' => 'integer',
        'max_attempts' => 'integer',
        'difficulty_split_json' => 'array',
        'origin_id' => 'integer',
        'version' => 'integer',
        'activation_expires_at' => 'datetime',
        'activation_used_at' => 'datetime',
    ];

    protected $attributes = [
        'version' => 1,
    ];

    public function topics()
    {
        return $this->belongsToMany(Topic::class, 'assessment_exam_topics', 'exam_id', 'topic_id');
    }

    public function questions()
    {
        return $this->belongsToMany(Question::class, 'assessment_exam_questions', 'exam_id', 'question_id')->withPivot('position')->orderBy('assessment_exam_questions.position');
    }
}
