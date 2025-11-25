<?php

/**
 * Temporary class aliases so existing host code that still references
 * `App\Assessments\...` continues to resolve after the package migration.
 */

$aliases = [
    \App\Assessments\Domain\Models\Topic::class => \Yami\Assessments\Domain\Models\Topic::class,
    \App\Assessments\Domain\Models\Question::class => \Yami\Assessments\Domain\Models\Question::class,
    \App\Assessments\Domain\Models\QuestionOption::class => \Yami\Assessments\Domain\Models\QuestionOption::class,
    \App\Assessments\Domain\Models\QuestionAnswer::class => \Yami\Assessments\Domain\Models\QuestionAnswer::class,
    \App\Assessments\Domain\Models\QuestionWidget::class => \Yami\Assessments\Domain\Models\QuestionWidget::class,
    \App\Assessments\Domain\Models\QuestionPlacement::class => \Yami\Assessments\Domain\Models\QuestionPlacement::class,
    \App\Assessments\Domain\Models\QuestionResponsePart::class => \Yami\Assessments\Domain\Models\QuestionResponsePart::class,
    \App\Assessments\Domain\Models\Answer::class => \Yami\Assessments\Domain\Models\Answer::class,
    \App\Assessments\Domain\Models\AnswerKey::class => \Yami\Assessments\Domain\Models\AnswerKey::class,
    \App\Assessments\Domain\Models\AnswerSet::class => \Yami\Assessments\Domain\Models\AnswerSet::class,
    \App\Assessments\Domain\Models\AnswerSetItem::class => \Yami\Assessments\Domain\Models\AnswerSetItem::class,
    \App\Assessments\Domain\Models\AnswerUsageAggregate::class => \Yami\Assessments\Domain\Models\AnswerUsageAggregate::class,
    \App\Assessments\Domain\Models\InputPreset::class => \Yami\Assessments\Domain\Models\InputPreset::class,
    \App\Assessments\Domain\Models\Exam::class => \Yami\Assessments\Domain\Models\Exam::class,
    \App\Assessments\Domain\Models\ExamPlacement::class => \Yami\Assessments\Domain\Models\ExamPlacement::class,
    \App\Assessments\Domain\Models\ExamRequirement::class => \Yami\Assessments\Domain\Models\ExamRequirement::class,
    \App\Assessments\Domain\Models\Attempt::class => \Yami\Assessments\Domain\Models\Attempt::class,
    \App\Assessments\Domain\Models\AttemptTextAnswer::class => \Yami\Assessments\Domain\Models\AttemptTextAnswer::class,
];

foreach ($aliases as $legacyClass => $packageClass) {
    if (!class_exists($legacyClass) && class_exists($packageClass)) {
        class_alias($packageClass, $legacyClass);
    }
}

$serviceProxies = [
    \Yami\Assessments\Services\PropagationService::class => \App\Assessments\Services\PropagationService::class,
    \Yami\Assessments\Services\ExamAssemblyService::class => \App\Assessments\Services\ExamAssemblyService::class,
    \Yami\Assessments\Services\AnswerUsageService::class => \App\Assessments\Services\AnswerUsageService::class,
    \Yami\Assessments\Services\SchemaHashService::class => \App\Assessments\Services\SchemaHashService::class,
];

foreach ($serviceProxies as $packageClass => $hostClass) {
    if (!class_exists($packageClass) && class_exists($hostClass)) {
        class_alias($hostClass, $packageClass);
    }
}
