<?php

/**
 * Temporary class aliases so existing host code that still references
 * `App\Assessments\...` continues to resolve after the package migration.
 */

$aliases = [
    \App\Assessments\Domain\Models\Topic::class => \Streaming\Assessments\Domain\Models\Topic::class,
    \App\Assessments\Domain\Models\Question::class => \Streaming\Assessments\Domain\Models\Question::class,
    \App\Assessments\Domain\Models\QuestionOption::class => \Streaming\Assessments\Domain\Models\QuestionOption::class,
    \App\Assessments\Domain\Models\QuestionAnswer::class => \Streaming\Assessments\Domain\Models\QuestionAnswer::class,
    \App\Assessments\Domain\Models\QuestionWidget::class => \Streaming\Assessments\Domain\Models\QuestionWidget::class,
    \App\Assessments\Domain\Models\QuestionPlacement::class => \Streaming\Assessments\Domain\Models\QuestionPlacement::class,
    \App\Assessments\Domain\Models\QuestionResponsePart::class => \Streaming\Assessments\Domain\Models\QuestionResponsePart::class,
    \App\Assessments\Domain\Models\Answer::class => \Streaming\Assessments\Domain\Models\Answer::class,
    \App\Assessments\Domain\Models\AnswerKey::class => \Streaming\Assessments\Domain\Models\AnswerKey::class,
    \App\Assessments\Domain\Models\AnswerSet::class => \Streaming\Assessments\Domain\Models\AnswerSet::class,
    \App\Assessments\Domain\Models\AnswerSetItem::class => \Streaming\Assessments\Domain\Models\AnswerSetItem::class,
    \App\Assessments\Domain\Models\AnswerUsageAggregate::class => \Streaming\Assessments\Domain\Models\AnswerUsageAggregate::class,
    \App\Assessments\Domain\Models\InputPreset::class => \Streaming\Assessments\Domain\Models\InputPreset::class,
    \App\Assessments\Domain\Models\Exam::class => \Streaming\Assessments\Domain\Models\Exam::class,
    \App\Assessments\Domain\Models\ExamPlacement::class => \Streaming\Assessments\Domain\Models\ExamPlacement::class,
    \App\Assessments\Domain\Models\ExamRequirement::class => \Streaming\Assessments\Domain\Models\ExamRequirement::class,
    \App\Assessments\Domain\Models\Attempt::class => \Streaming\Assessments\Domain\Models\Attempt::class,
    \App\Assessments\Domain\Models\AttemptTextAnswer::class => \Streaming\Assessments\Domain\Models\AttemptTextAnswer::class,
];

foreach ($aliases as $legacyClass => $packageClass) {
    if (!class_exists($legacyClass) && class_exists($packageClass)) {
        class_alias($packageClass, $legacyClass);
    }
}

$serviceProxies = [
    \Streaming\Assessments\Services\PropagationService::class => \App\Assessments\Services\PropagationService::class,
    \Streaming\Assessments\Services\ExamAssemblyService::class => \App\Assessments\Services\ExamAssemblyService::class,
    \Streaming\Assessments\Services\AnswerUsageService::class => \App\Assessments\Services\AnswerUsageService::class,
    \Streaming\Assessments\Services\SchemaHashService::class => \App\Assessments\Services\SchemaHashService::class,
];

foreach ($serviceProxies as $packageClass => $hostClass) {
    if (!class_exists($packageClass) && class_exists($hostClass)) {
        class_alias($hostClass, $packageClass);
    }
}
