<?php

namespace Amryami\Assessments\Http\Middleware;

use Amryami\Assessments\Domain\Models\{ExamRequirement, Exam};
use Amryami\Assessments\Support\ModelResolver;
use Closure;
use Illuminate\Http\Request;

class ExamGate
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (!$user) return $next($request);

        // Resolve category settings
        $categoryId = $user->category_id ?? null;
        $categoryClass = $this->categoryModel();
        $category = $categoryId ? $categoryClass::find($categoryId) : null;
        if (!$category || ($category->exam_trigger ?? 'none') === 'none') {
            return $next($request);
        }

        // Ensure requirement exists as per trigger
        $requirement = ExamRequirement::where('user_id',$user->id)->first();
        if (!$requirement) {
            // Trigger conditions
            $triggerOk = false;
            if (($category->exam_trigger) === 'on_register') { $triggerOk = true; }
            if (($category->exam_trigger) === 'after_approval' && (int)($user->approve ?? 0) === 1) { $triggerOk = true; }
            // Assign if exam is published
            if ($triggerOk && ($category->entrance_exam_id)) {
                $exam = Exam::find($category->entrance_exam_id);
                if ($exam && ($exam->is_published || ($exam->status ?? 'draft')==='published')) {
                    ExamRequirement::create([
                        'user_id' => $user->id,
                        'exam_id' => $exam->id,
                        'status' => 'required',
                        'attempts_used' => 0,
                        'max_attempts' => $exam->max_attempts,
                        'assigned_at' => now(),
                        'fail_action' => $category->on_fail_action ?? 'block_profile',
                    ]);
                }
            }
            $requirement = ExamRequirement::where('user_id',$user->id)->first();
        }

        if (!$requirement) return $next($request);

        // Gate logic
        if (in_array($requirement->status, ['required','in_progress'])) {
            // Allow exam routes only
            if ($this->isAllowedExamRoute($request)) return $next($request);
            return redirect()->route('assessments.candidate.exams.index')->with('error','You must pass the entrance exam to continue.');
        }

        if ($requirement->status === 'failed') {
            $action = $requirement->fail_action ?? 'block_profile';
            if ($action === 'reject') {
                if ($this->isAuthOrExamRoute($request)) return $next($request);
                return redirect()->route('rejected');
            }
            if ($action === 'block_profile' || $action === 'block_profile_reject') {
                // Show dedicated rejected landing (no sidebar/actions)
                if ($this->isAuthOrExamRoute($request)) return $next($request);
                return redirect()->route('rejected');
            }
            // allow_profile / allow_profile_reject → allow
        }

        return $next($request);
    }

    private function isAllowedExamRoute(Request $request): bool
    {
        $name = optional($request->route())->getName();
        return in_array($name, [
            'assessments.candidate.exams.index',
            'assessments.candidate.exams.preview',
            'assessments.candidate.exams.results',
        ]) || str_starts_with($request->path(), 'api/attempts') || str_starts_with($request->path(), 'api/exams');
    }

    private function isAuthOrExamRoute(Request $request): bool
    {
        $name = optional($request->route())->getName();
        return $this->isAllowedExamRoute($request) || in_array($name, ['login','logout','rejected']);
    }

    protected function categoryModel(): string
    {
        return ModelResolver::category();
    }
}
