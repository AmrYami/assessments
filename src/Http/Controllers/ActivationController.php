<?php

namespace Amryami\Assessments\Http\Controllers;

use Amryami\Assessments\Domain\Models\Exam;
use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Route;

class ActivationController extends Controller
{
    public function __invoke(Exam $exam, string $token)
    {
        $expected = (string) $exam->activation_token;
        if ($expected === '' || !hash_equals($expected, (string) $token)) {
            abort(404);
        }

        $now = Carbon::now();
        if ($exam->activation_used_at) {
            abort(410, 'Activation link has already been used.');
        }

        if ($exam->activation_expires_at && $now->greaterThan($exam->activation_expires_at)) {
            abort(410, 'Activation link has expired.');
        }

        $exam->forceFill([
            'is_published' => true,
            'status' => 'published',
            'activation_used_at' => $now,
            'activation_token' => null,
        ])->save();

        $redirect = $exam->activation_path ?: null;
        if (!$redirect) {
            $routeName = config('assessments.activation.redirect_route', 'assessments.candidate.exams.preview');
            $redirect = Route::has($routeName)
                ? route($routeName, $exam)
                : url('/');
        }

        return redirect()->to($redirect);
    }
}
