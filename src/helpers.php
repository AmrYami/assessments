<?php

/**
 * Temporary class aliases so existing host code that still references
 * `App\Assessments\...` continues to resolve after the package migration.
 */

$aliases = [
    \App\Assessments\Domain\Models\Topic::class => \Amryami\Assessments\Domain\Models\Topic::class,
    \App\Assessments\Domain\Models\Question::class => \Amryami\Assessments\Domain\Models\Question::class,
    \App\Assessments\Domain\Models\QuestionOption::class => \Amryami\Assessments\Domain\Models\QuestionOption::class,
    \App\Assessments\Domain\Models\QuestionAnswer::class => \Amryami\Assessments\Domain\Models\QuestionAnswer::class,
    \App\Assessments\Domain\Models\QuestionWidget::class => \Amryami\Assessments\Domain\Models\QuestionWidget::class,
    \App\Assessments\Domain\Models\QuestionPlacement::class => \Amryami\Assessments\Domain\Models\QuestionPlacement::class,
    \App\Assessments\Domain\Models\QuestionResponsePart::class => \Amryami\Assessments\Domain\Models\QuestionResponsePart::class,
    \App\Assessments\Domain\Models\Answer::class => \Amryami\Assessments\Domain\Models\Answer::class,
    \App\Assessments\Domain\Models\AnswerKey::class => \Amryami\Assessments\Domain\Models\AnswerKey::class,
    \App\Assessments\Domain\Models\AnswerSet::class => \Amryami\Assessments\Domain\Models\AnswerSet::class,
    \App\Assessments\Domain\Models\AnswerSetItem::class => \Amryami\Assessments\Domain\Models\AnswerSetItem::class,
    \App\Assessments\Domain\Models\AnswerUsageAggregate::class => \Amryami\Assessments\Domain\Models\AnswerUsageAggregate::class,
    \App\Assessments\Domain\Models\InputPreset::class => \Amryami\Assessments\Domain\Models\InputPreset::class,
    \App\Assessments\Domain\Models\Exam::class => \Amryami\Assessments\Domain\Models\Exam::class,
    \App\Assessments\Domain\Models\ExamPlacement::class => \Amryami\Assessments\Domain\Models\ExamPlacement::class,
    \App\Assessments\Domain\Models\ExamRequirement::class => \Amryami\Assessments\Domain\Models\ExamRequirement::class,
    \App\Assessments\Domain\Models\Attempt::class => \Amryami\Assessments\Domain\Models\Attempt::class,
    \App\Assessments\Domain\Models\AttemptTextAnswer::class => \Amryami\Assessments\Domain\Models\AttemptTextAnswer::class,
];

foreach ($aliases as $legacyClass => $packageClass) {
    if (!class_exists($legacyClass) && class_exists($packageClass)) {
        class_alias($packageClass, $legacyClass);
    }
}

$serviceProxies = [
    \Amryami\Assessments\Services\PropagationService::class => \App\Assessments\Services\PropagationService::class,
    \Amryami\Assessments\Services\ExamAssemblyService::class => \App\Assessments\Services\ExamAssemblyService::class,
    \Amryami\Assessments\Services\AnswerUsageService::class => \App\Assessments\Services\AnswerUsageService::class,
    \Amryami\Assessments\Services\SchemaHashService::class => \App\Assessments\Services\SchemaHashService::class,
];

foreach ($serviceProxies as $packageClass => $hostClass) {
    if (!class_exists($packageClass) && class_exists($hostClass)) {
        class_alias($hostClass, $packageClass);
    }
}
