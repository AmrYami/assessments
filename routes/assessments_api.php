<?php

use Streaming\Assessments\Http\Controllers\Admin\{
    AnswerSetApiController,
    ExamController,
    PresetController,
    PropagationApiController,
    QuestionController,
    ReportApiController
};
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;

if (!config('assessments.enabled', true) || !config('assessments.admin_only', true)) {
    return;
}

$apiMiddleware = config('assessments.middleware.admin_api');
if (!is_array($apiMiddleware)) {
    $apiMiddleware = array_filter([$apiMiddleware]);
}
$apiMiddleware = array_values(array_unique(array_filter(
    array_merge(
        $apiMiddleware ?: ['web', 'auth:web'],
        [SubstituteBindings::class]
    )
)));
if (!in_array('web', $apiMiddleware, true)) {
    array_unshift($apiMiddleware, 'web');
}
$apiMiddleware = array_values(array_unique($apiMiddleware));

$adminApiPrefix = trim((string) config('assessments.routes.admin_api_prefix', 'dashboard'), '/');
$adminApiNamePrefix = trim((string) config('assessments.routes.admin_api_name_prefix', 'dashboard'), '.');

$registrar = Route::middleware($apiMiddleware);
if ($adminApiPrefix !== '') {
    $registrar = $registrar->prefix($adminApiPrefix);
}
$registrar = $registrar->as($adminApiNamePrefix !== '' ? "{$adminApiNamePrefix}." : '');

$registrar->group(function () {
    Route::get('assessments/api/answer-sets', [AnswerSetApiController::class, 'index'])
        ->middleware('permission:exams.answersets.index')
        ->name('assessments.api.answer_sets.index');
    Route::post('assessments/api/answer-sets', [AnswerSetApiController::class, 'store'])
        ->middleware('permission:exams.answersets.store')
        ->name('assessments.api.answer_sets.store');

    Route::post('assessments/questions/{question}/answer-sets/link', [AnswerSetApiController::class, 'link'])
        ->middleware('permission:exams.questions.update')
        ->name('assessments.api.questions.answer_sets.link');
    Route::post('assessments/questions/{question}/answer-sets/unlink', [AnswerSetApiController::class, 'unlink'])
        ->middleware('permission:exams.questions.update')
        ->name('assessments.api.questions.answer_sets.unlink');

    Route::post('assessments/questions/{question}/propagate', [PropagationApiController::class, 'propagateQuestion'])
        ->middleware('permission:exams.propagate.questions')
        ->name('assessments.api.questions.propagate');
    Route::post('assessments/questions/{question}/propagate/preview', [PropagationApiController::class, 'previewQuestion'])
        ->middleware('permission:exams.propagate.questions')
        ->name('assessments.api.questions.propagate.preview');

    Route::post('assessments/exams/{exam}/propagate', [PropagationApiController::class, 'propagateExam'])
        ->middleware('permission:exams.propagate.exams')
        ->name('assessments.api.exams.propagate');
    Route::post('assessments/exams/{exam}/propagate/preview', [PropagationApiController::class, 'previewExam'])
        ->middleware('permission:exams.propagate.exams')
        ->name('assessments.api.exams.propagate.preview');

    Route::get('assessments/exams/{exam}/coverage', [ExamController::class, 'coverage'])
        ->middleware('permission:exams.exams.preview')
        ->name('assessments.api.exams.coverage');

    Route::get('assessments/api/presets', [PresetController::class, 'index'])
        ->middleware('permission:exams.presets.index')
        ->name('assessments.api.presets.index');

    Route::get('assessments/api/questions/search', [QuestionController::class, 'search'])
        ->middleware('permission:exams.questions.index')
        ->name('assessments.api.questions.search');
    Route::get('assessments/api/questions/{question}/answers', [QuestionController::class, 'answers'])
        ->middleware('permission:exams.questions.index')
        ->name('assessments.api.questions.answers');

    Route::get('assessments/api/reports', [ReportApiController::class, 'index'])
        ->middleware('permission:exams.reports.index')
        ->name('assessments.api.reports.index');

    Route::get('assessments/api/reports/{exam}', [ReportApiController::class, 'show'])
        ->middleware('permission:exams.reports.index')
        ->name('assessments.api.reports.show');
});
