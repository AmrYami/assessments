<?php

use Fakeeh\Assessments\Http\Controllers\Candidate\ExamController;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;

$candidateMiddleware = config('assessments.middleware.candidate');
if (!is_array($candidateMiddleware)) {
    $candidateMiddleware = array_filter([$candidateMiddleware]);
}
$candidateMiddleware = array_values(array_unique(array_filter(
    array_merge(
        $candidateMiddleware ?: ['web', 'auth:web'],
        [SubstituteBindings::class]
    )
)));
if (!in_array('web', $candidateMiddleware, true)) {
    array_unshift($candidateMiddleware, 'web');
}
$candidateMiddleware = array_values(array_unique($candidateMiddleware));

$candidatePrefix = trim((string) config('assessments.routes.candidate_prefix', ''), '/');
$candidateNamePrefix = trim((string) config('assessments.routes.candidate_name_prefix', 'assessments.candidate'), '.');

$registrar = Route::middleware($candidateMiddleware);
if ($candidatePrefix !== '') {
    $registrar = $registrar->prefix($candidatePrefix);
}
$registrar = $registrar->as($candidateNamePrefix !== '' ? "{$candidateNamePrefix}." : '');

$registrar->group(function () {
    Route::get('exams', [ExamController::class, 'index'])
        ->name('exams.index');
    Route::get('exams/{exam}/preview', [ExamController::class, 'preview'])
        ->name('exams.preview');
    Route::get('exams/results', [ExamController::class, 'results'])
        ->name('exams.results');
});
