<?php

namespace Yami\Assessments\Domain\Models;

use Yami\Assessments\Services\QuestionPoolCache;
use Yami\Assessments\Support\BaseModel;
use Yami\Assessments\Support\ModelResolver;

class Question extends BaseModel
{
    protected $table = 'assessment_questions';

    protected $fillable = [
        'slug',
        'text',
        'response_type',
        'weight',
        'difficulty',
        'is_active',
        'note_enabled',
        'note_required',
        'note_hint',
        'max_choices',
        'origin_id',
        'version',
        'explanation',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'weight' => 'integer',
        'note_enabled' => 'boolean',
        'note_required' => 'boolean',
        'max_choices' => 'integer',
        'origin_id' => 'integer',
        'version' => 'integer',
    ];

    protected $attributes = [
        'response_type' => 'single_choice',
        'version' => 1,
    ];

    public function topics()
    {
        return $this->belongsToMany(Topic::class, 'assessment_question_topics', 'question_id', 'topic_id');
    }

    public function options()
    {
        return $this->hasMany(QuestionOption::class, 'question_id');
    }

    public function answerKeys()
    {
        return $this->hasMany(AnswerKey::class, 'question_id');
    }

    public function categories()
    {
        $categoryModel = ModelResolver::category();

        return $this->belongsToMany($categoryModel, 'assessment_question_categories', 'question_id', 'category_id');
    }

    public function answerLinks()
    {
        return $this->hasMany(QuestionAnswer::class, 'question_id')->orderBy('position');
    }

    public function widgets()
    {
        return $this->hasMany(QuestionWidget::class, 'question_id')->orderBy('position');
    }

    public function responseParts()
    {
        return $this->hasMany(QuestionResponsePart::class, 'question_id')->orderBy('position');
    }

    public function getSelectionModeAttribute(): string
    {
        return $this->response_type === 'multiple_choice' ? 'multiple' : 'single';
    }

    public function setSelectionModeAttribute($value): void
    {
        if ($value === 'multiple') {
            $this->attributes['response_type'] = 'multiple_choice';
        } elseif ($value === 'single') {
            $this->attributes['response_type'] = 'single_choice';
        }
    }

    protected static function booted(): void
    {
        $flush = function () {
            app(QuestionPoolCache::class)->flush();
        };

        static::saved(function (self $model) use ($flush) {
            $flush();
        });

        static::deleted(function (self $model) use ($flush) {
            $flush();
        });

        static::registerModelEvent('pivotAttached', function (...$args) use ($flush) {
            $flush();
        });

        static::registerModelEvent('pivotDetached', function (...$args) use ($flush) {
            $flush();
        });

        static::registerModelEvent('pivotUpdated', function (...$args) use ($flush) {
            $flush();
        });
    }
}
