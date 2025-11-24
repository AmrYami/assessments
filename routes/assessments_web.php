<?php

use Amryami\Assessments\Http\Controllers\Admin\{
    AnswerSetController,
    ExamController,
    PresetController,
    QuestionController,
    ReviewController,
    TopicController,
    VersionController,
    ReportController
};
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;

if (!config('assessments.enabled', true) || !config('assessments.admin_only', true)) {
    return;
}

$adminMiddleware = config('assessments.middleware.admin');
if (!is_array($adminMiddleware)) {
    $adminMiddleware = array_filter([$adminMiddleware]);
}
$adminMiddleware = array_values(array_unique(array_filter(
    array_merge(
        $adminMiddleware ?: ['web', 'auth:web'],
        [SubstituteBindings::class]
    )
)));
if (!in_array('web', $adminMiddleware, true)) {
    array_unshift($adminMiddleware, 'web');
}
$adminMiddleware = array_values(array_unique($adminMiddleware));

$adminPrefix = trim((string) config('assessments.routes.admin_prefix', 'dashboard'), '/');
$adminNamePrefix = trim((string) config('assessments.routes.admin_name_prefix', 'dashboard'), '.');

$routes = Route::middleware($adminMiddleware);
if ($adminPrefix !== '') {
    $routes = $routes->prefix($adminPrefix);
}
$routes = $routes->as($adminNamePrefix !== '' ? "{$adminNamePrefix}." : '');

$routes->group(function () {
    // Topics
    Route::resource('assessments/topics', TopicController::class)->names('assessments.topics')
        ->middlewareFor(['index', 'show'], 'permission:exams.topics.index')
        ->middlewareFor('create', 'permission:exams.topics.create')
        ->middlewareFor('store', 'permission:exams.topics.store')
        ->middlewareFor('edit', 'permission:exams.topics.edit')
        ->middlewareFor('update', 'permission:exams.topics.update')
        ->middlewareFor('destroy', 'permission:exams.topics.destroy');

    // History & diff views
    Route::get('assessments/questions/diff', [VersionController::class, 'questionDiff'])
        ->name('assessments.questions.diff');
    Route::get('assessments/questions/{question}/history', [VersionController::class, 'questionHistory'])
        ->name('assessments.questions.history');
    Route::get('assessments/exams/diff', [VersionController::class, 'examDiff'])
        ->name('assessments.exams.diff');
    Route::get('assessments/exams/{exam}/history', [VersionController::class, 'examHistory'])
        ->name('assessments.exams.history');

    // Questions
    Route::resource('assessments/questions', QuestionController::class)->names('assessments.questions')
        ->middlewareFor(['index', 'show'], 'permission:exams.questions.index')
        ->middlewareFor('create', 'permission:exams.questions.create')
        ->middlewareFor('store', 'permission:exams.questions.store')
        ->middlewareFor('edit', 'permission:exams.questions.edit')
        ->middlewareFor('update', 'permission:exams.questions.update')
        ->middlewareFor('destroy', 'permission:exams.questions.destroy');

    // Exams
    Route::post('assessments/exams/{exam}/publish', [ExamController::class, 'publish'])
        ->middleware('permission:exams.exams.publish')
        ->name('assessments.exams.publish');
    Route::post('assessments/exams/{exam}/unpublish', [ExamController::class, 'unpublish'])
        ->middleware('permission:exams.exams.publish')
        ->name('assessments.exams.unpublish');
    Route::post('assessments/exams/{exam}/archive', [ExamController::class, 'archive'])
        ->middleware('permission:exams.exams.publish')
        ->name('assessments.exams.archive');

    Route::get('assessments/exams/{exam}/preview', [ExamController::class, 'preview'])
        ->middleware('permission:exams.exams.preview')
        ->name('assessments.exams.preview');

    Route::resource('assessments/exams', ExamController::class)->names('assessments.exams')
        ->middlewareFor(['index', 'show'], 'permission:exams.exams.index')
        ->middlewareFor('create', 'permission:exams.exams.create')
        ->middlewareFor('store', 'permission:exams.exams.store')
        ->middlewareFor('edit', 'permission:exams.exams.edit')
        ->middlewareFor('update', 'permission:exams.exams.update')
        ->middlewareFor('destroy', 'permission:exams.exams.destroy');

    // Answer Sets (UI)
    Route::resource('assessments/answer-sets', AnswerSetController::class)->names('assessments.answer_sets')
        ->middlewareFor(['index', 'show'], 'permission:exams.answersets.index')
        ->middlewareFor('create', 'permission:exams.answersets.create')
        ->middlewareFor('store', 'permission:exams.answersets.store')
        ->middlewareFor('edit', 'permission:exams.answersets.edit')
        ->middlewareFor('update', 'permission:exams.answersets.update')
        ->middlewareFor('destroy', 'permission:exams.answersets.destroy');
    Route::post('assessments/answer-sets/{answerSet}/restore', [AnswerSetController::class, 'restore'])
        ->middleware('permission:exams.answersets.update')
        ->name('assessments.answer_sets.restore');

    // Preset library (UI)
    Route::resource('assessments/presets', PresetController::class)->names('assessments.presets')
        ->middlewareFor(['index', 'show'], 'permission:exams.answers.update')
        ->middlewareFor('create', 'permission:exams.answers.update')
        ->middlewareFor('store', 'permission:exams.answers.update')
        ->middlewareFor('edit', 'permission:exams.answers.update')
        ->middlewareFor('update', 'permission:exams.answers.update')
        ->middlewareFor('destroy', 'permission:exams.answers.update');

    // Reviews workspace
    Route::get('assessments/reviews', [ReviewController::class, 'index'])
        ->name('assessments.reviews.index');
    Route::get('assessments/reviews/{attempt}', [ReviewController::class, 'show'])
        ->name('assessments.reviews.show');
    Route::patch('assessments/reviews/{attempt}', [ReviewController::class, 'update'])
        ->name('assessments.reviews.update');

    Route::get('assessments/reports', [ReportController::class, 'index'])
        ->middleware('permission:exams.reports.index')
        ->name('assessments.reports.index');
    Route::get('assessments/reports/export', [ReportController::class, 'export'])
        ->middleware('permission:exams.reports.index')
        ->name('assessments.reports.export');
    Route::get('assessments/reports/export/json', [ReportController::class, 'exportJson'])
        ->middleware('permission:exams.reports.index')
        ->name('assessments.reports.export_json');
});
