<?php

namespace Fakeeh\Assessments\Http\Controllers\Admin;

use Fakeeh\Assessments\Support\Controller;
use Fakeeh\Assessments\Domain\Models\ExamRequirement;
use Fakeeh\Assessments\Domain\Models\Exam;
use Fakeeh\Assessments\Support\ModelResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;

class EntranceExamAdminController extends Controller
{
    public function grantRetake($category, $user): RedirectResponse
    {
        $category = $this->resolveCategory($category);
        $user = $this->resolveUser($user);

        $req = ExamRequirement::where('user_id', $user->id)->first();
        if (!$req) {
            if (!$category->entrance_exam_id) {
                return back()->with('error', 'No entrance exam configured.');
            }
            $exam = Exam::find($category->entrance_exam_id);
            if (!$exam || !($exam->is_published || ($exam->status ?? 'draft') === 'published')) {
                return back()->with('error', 'Entrance exam not published.');
            }
            $req = ExamRequirement::create([
                'user_id' => $user->id,
                'exam_id' => $exam->id,
                'status' => 'required',
                'attempts_used' => 0,
                'max_attempts' => $exam->max_attempts,
                'assigned_at' => now(),
                'fail_action' => $category->on_fail_action ?? 'block_profile',
            ]);
        }
        $req->max_attempts = (int) ($req->max_attempts ?? 0) + 1;
        $req->save();
        return back()->with('success', 'Granted one extra retake.');
    }

    public function overridePassed($category, $user): RedirectResponse
    {
        $category = $this->resolveCategory($category);
        $user = $this->resolveUser($user);

        $req = ExamRequirement::where('user_id', $user->id)->first();
        if ($req) {
            $req->status = 'passed';
            $req->save();
        }
        return back()->with('success', 'Entrance exam overridden to Passed.');
    }

    public function rejectCandidate($category, $user): RedirectResponse
    {
        $category = $this->resolveCategory($category);
        $user = $this->resolveUser($user);

        $req = ExamRequirement::where('user_id', $user->id)->first();
        if ($req) {
            $req->status = 'failed';
            $req->save();
        }
        $user->status = 'rejected';
        if (property_exists($user, 'approve')) {
            $user->approve = 0;
        }
        $user->save();
        return back()->with('success', 'Candidate rejected.');
    }

    protected function resolveCategory($category): Model
    {
        $model = ModelResolver::category();
        if ($category instanceof Model) {
            return $category;
        }
        return $model::findOrFail($category);
    }

    protected function resolveUser($user): Model
    {
        $model = ModelResolver::user();
        if ($user instanceof Model) {
            return $user;
        }
        return $model::findOrFail($user);
    }
}
