<?php

/**
 * Temporary class aliases so existing host code that still references
 * `App\Assessments\...` continues to resolve after the package migration.
 */

$aliases = [
    \App\Assessments\Domain\Models\Topic::class => \Fakeeh\Assessments\Domain\Models\Topic::class,
    \App\Assessments\Domain\Models\Question::class => \Fakeeh\Assessments\Domain\Models\Question::class,
    \App\Assessments\Domain\Models\QuestionOption::class => \Fakeeh\Assessments\Domain\Models\QuestionOption::class,
    \App\Assessments\Domain\Models\QuestionAnswer::class => \Fakeeh\Assessments\Domain\Models\QuestionAnswer::class,
    \App\Assessments\Domain\Models\QuestionWidget::class => \Fakeeh\Assessments\Domain\Models\QuestionWidget::class,
    \App\Assessments\Domain\Models\QuestionPlacement::class => \Fakeeh\Assessments\Domain\Models\QuestionPlacement::class,
    \App\Assessments\Domain\Models\QuestionResponsePart::class => \Fakeeh\Assessments\Domain\Models\QuestionResponsePart::class,
    \App\Assessments\Domain\Models\Answer::class => \Fakeeh\Assessments\Domain\Models\Answer::class,
    \App\Assessments\Domain\Models\AnswerKey::class => \Fakeeh\Assessments\Domain\Models\AnswerKey::class,
    \App\Assessments\Domain\Models\AnswerSet::class => \Fakeeh\Assessments\Domain\Models\AnswerSet::class,
    \App\Assessments\Domain\Models\AnswerSetItem::class => \Fakeeh\Assessments\Domain\Models\AnswerSetItem::class,
    \App\Assessments\Domain\Models\AnswerUsageAggregate::class => \Fakeeh\Assessments\Domain\Models\AnswerUsageAggregate::class,
    \App\Assessments\Domain\Models\InputPreset::class => \Fakeeh\Assessments\Domain\Models\InputPreset::class,
    \App\Assessments\Domain\Models\Exam::class => \Fakeeh\Assessments\Domain\Models\Exam::class,
    \App\Assessments\Domain\Models\ExamPlacement::class => \Fakeeh\Assessments\Domain\Models\ExamPlacement::class,
    \App\Assessments\Domain\Models\ExamRequirement::class => \Fakeeh\Assessments\Domain\Models\ExamRequirement::class,
    \App\Assessments\Domain\Models\Attempt::class => \Fakeeh\Assessments\Domain\Models\Attempt::class,
    \App\Assessments\Domain\Models\AttemptTextAnswer::class => \Fakeeh\Assessments\Domain\Models\AttemptTextAnswer::class,
];

foreach ($aliases as $legacyClass => $packageClass) {
    if (!class_exists($legacyClass) && class_exists($packageClass)) {
        class_alias($packageClass, $legacyClass);
    }
}

$serviceProxies = [
    \Fakeeh\Assessments\Services\PropagationService::class => \App\Assessments\Services\PropagationService::class,
    \Fakeeh\Assessments\Services\ExamAssemblyService::class => \App\Assessments\Services\ExamAssemblyService::class,
    \Fakeeh\Assessments\Services\AnswerUsageService::class => \App\Assessments\Services\AnswerUsageService::class,
    \Fakeeh\Assessments\Services\SchemaHashService::class => \App\Assessments\Services\SchemaHashService::class,
];

foreach ($serviceProxies as $packageClass => $hostClass) {
    if (!class_exists($packageClass) && class_exists($hostClass)) {
        class_alias($hostClass, $packageClass);
    }
}
