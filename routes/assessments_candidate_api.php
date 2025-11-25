<?php

use Yami\Assessments\Http\Controllers\Candidate\AttemptApiController;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;

$candidateApiMiddleware = config('assessments.middleware.candidate_api');
if (!is_array($candidateApiMiddleware)) {
    $candidateApiMiddleware = array_filter([$candidateApiMiddleware]);
}
$candidateApiMiddleware = array_values(array_unique(array_filter(
    array_merge(
        $candidateApiMiddleware ?: ['web', 'auth:web'],
        [SubstituteBindings::class]
    )
)));
if (!in_array('web', $candidateApiMiddleware, true)) {
    array_unshift($candidateApiMiddleware, 'web');
}
$candidateApiMiddleware = array_values(array_unique($candidateApiMiddleware));

$candidateApiPrefix = trim((string) config('assessments.routes.candidate_api_prefix', 'api'), '/');
$candidateApiNamePrefix = trim((string) config('assessments.routes.candidate_api_name_prefix', ''), '.');

$registrar = Route::middleware($candidateApiMiddleware);
if ($candidateApiPrefix !== '') {
    $registrar = $registrar->prefix($candidateApiPrefix);
}
$registrar = $registrar->as($candidateApiNamePrefix !== '' ? "{$candidateApiNamePrefix}." : '');

$registrar->group(function () {
    Route::post('exams/{exam}/attempts', [AttemptApiController::class, 'start'])
        ->middleware('permission:exams.attempts.start');
    Route::get('attempts/{attempt}/heartbeat', [AttemptApiController::class, 'heartbeat'])
        ->middleware('permission:exams.attempts.start');
    Route::patch('attempts/{attempt}/answers', [AttemptApiController::class, 'saveAnswers'])
        ->middleware('permission:exams.attempts.answer');
    Route::post('attempts/{attempt}/submit', [AttemptApiController::class, 'submit'])
        ->middleware('permission:exams.attempts.submit');
    Route::get('attempts/{attempt}/result', [AttemptApiController::class, 'result'])
        ->middleware('permission:exams.attempts.view_result');
});
