<?php

namespace Amryami\Assessments\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class AssessmentsPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // reset cached permissions so new rows are recognized immediately
        try { app(PermissionRegistrar::class)->forgetCachedPermissions(); } catch (\Throwable $e) {}
        $guards = config('auth.guards');
        foreach ($guards as $guardName => $guardConfig) {
            if (!in_array($guardName, ['api', 'sanctum'])) {
                DB::table('permissions')->insertOrIgnore([
                    // Topics
                    ["name" => "exams.topics.index", "display_name" => "List Topics", "description" => "Access topics list", "permission_group" => "assessments", "guard_name" => $guardName, "created_at" => Carbon::now()->toDateTimeString()],
                    ["name" => "exams.topics.create", "display_name" => "Create Topic", "description" => "Create topic", "permission_group" => "assessments", "guard_name" => $guardName, "created_at" => Carbon::now()->toDateTimeString()],
                    ["name" => "exams.topics.store", "display_name" => "Store Topic", "description" => "Store topic", "permission_group" => "assessments", "guard_name" => $guardName, "created_at" => Carbon::now()->toDateTimeString()],
                    ["name" => "exams.topics.edit", "display_name" => "Edit Topic", "description" => "Edit topic", "permission_group" => "assessments", "guard_name" => $guardName, "created_at" => Carbon::now()->toDateTimeString()],
                    ["name" => "exams.topics.update", "display_name" => "Update Topic", "description" => "Update topic", "permission_group" => "assessments", "guard_name" => $guardName, "created_at" => Carbon::now()->toDateTimeString()],
                    ["name" => "exams.topics.destroy", "display_name" => "Delete Topic", "description" => "Delete topic", "permission_group" => "assessments", "guard_name" => $guardName, "created_at" => Carbon::now()->toDateTimeString()],

                    // Questions
                    ["name" => "exams.questions.index", "display_name" => "List Questions", "description" => "Access questions list", "permission_group" => "assessments", "guard_name" => $guardName, "created_at" => Carbon::now()->toDateTimeString()],
                    ["name" => "exams.questions.create", "display_name" => "Create Question", "description" => "Create question", "permission_group" => "assessments", "guard_name" => $guardName, "created_at" => Carbon::now()->toDateTimeString()],
                    ["name" => "exams.questions.store", "display_name" => "Store Question", "description" => "Store question", "permission_group" => "assessments", "guard_name" => $guardName, "created_at" => Carbon::now()->toDateTimeString()],
                    ["name" => "exams.questions.edit", "display_name" => "Edit Question", "description" => "Edit question", "permission_group" => "assessments", "guard_name" => $guardName, "created_at" => Carbon::now()->toDateTimeString()],
                    ["name" => "exams.questions.update", "display_name" => "Update Question", "description" => "Update question", "permission_group" => "assessments", "guard_name" => $guardName, "created_at" => Carbon::now()->toDateTimeString()],
                    ["name" => "exams.questions.destroy", "display_name" => "Delete Question", "description" => "Delete question", "permission_group" => "assessments", "guard_name" => $guardName, "created_at" => Carbon::now()->toDateTimeString()],

                    // Exams
                    ["name" => "exams.exams.index", "display_name" => "List Exams", "description" => "Access exams list", "permission_group" => "assessments", "guard_name" => $guardName, "created_at" => Carbon::now()->toDateTimeString()],
                    ["name" => "exams.exams.create", "display_name" => "Create Exam", "description" => "Create exam", "permission_group" => "assessments", "guard_name" => $guardName, "created_at" => Carbon::now()->toDateTimeString()],
                    ["name" => "exams.exams.store", "display_name" => "Store Exam", "description" => "Store exam", "permission_group" => "assessments", "guard_name" => $guardName, "created_at" => Carbon::now()->toDateTimeString()],
                    ["name" => "exams.exams.edit", "display_name" => "Edit Exam", "description" => "Edit exam", "permission_group" => "assessments", "guard_name" => $guardName, "created_at" => Carbon::now()->toDateTimeString()],
                    ["name" => "exams.exams.update", "display_name" => "Update Exam", "description" => "Update exam", "permission_group" => "assessments", "guard_name" => $guardName, "created_at" => Carbon::now()->toDateTimeString()],
                    ["name" => "exams.exams.destroy", "display_name" => "Delete Exam", "description" => "Delete exam", "permission_group" => "assessments", "guard_name" => $guardName, "created_at" => Carbon::now()->toDateTimeString()],
                    ["name" => "exams.exams.publish", "display_name" => "Publish Exam", "description" => "Publish exam", "permission_group" => "assessments", "guard_name" => $guardName, "created_at" => Carbon::now()->toDateTimeString()],
                    ["name" => "exams.exams.preview", "display_name" => "Preview Exam", "description" => "Preview exam", "permission_group" => "assessments", "guard_name" => $guardName, "created_at" => Carbon::now()->toDateTimeString()],

                    // Answers Library
                    ["name" => "exams.answers.index", "display_name" => "List Answers Library", "description" => "Search/list answers library", "permission_group" => "assessments", "guard_name" => $guardName, "created_at" => Carbon::now()->toDateTimeString()],
                    ["name" => "exams.answers.store", "display_name" => "Create Answer", "description" => "Create answers library items", "permission_group" => "assessments", "guard_name" => $guardName, "created_at" => Carbon::now()->toDateTimeString()],
                    ["name" => "exams.answers.update", "display_name" => "Update Answer", "description" => "Update answers library items", "permission_group" => "assessments", "guard_name" => $guardName, "created_at" => Carbon::now()->toDateTimeString()],
                    ["name" => "exams.answers.destroy", "display_name" => "Delete Answer", "description" => "Delete answers library items", "permission_group" => "assessments", "guard_name" => $guardName, "created_at" => Carbon::now()->toDateTimeString()],

                    // Answer Sets
                    ["name" => "exams.answersets.index", "display_name" => "List Answer Sets", "description" => "Access answer sets list", "permission_group" => "assessments", "guard_name" => $guardName, "created_at" => Carbon::now()->toDateTimeString()],
                    ["name" => "exams.answersets.create", "display_name" => "Create Answer Set", "description" => "Create new answer sets", "permission_group" => "assessments", "guard_name" => $guardName, "created_at" => Carbon::now()->toDateTimeString()],
                    ["name" => "exams.answersets.store", "display_name" => "Store Answer Set", "description" => "Store answer sets", "permission_group" => "assessments", "guard_name" => $guardName, "created_at" => Carbon::now()->toDateTimeString()],
                    ["name" => "exams.answersets.edit", "display_name" => "Edit Answer Set", "description" => "Edit answer sets", "permission_group" => "assessments", "guard_name" => $guardName, "created_at" => Carbon::now()->toDateTimeString()],
                    ["name" => "exams.answersets.update", "display_name" => "Update Answer Set", "description" => "Update answer sets", "permission_group" => "assessments", "guard_name" => $guardName, "created_at" => Carbon::now()->toDateTimeString()],
                    ["name" => "exams.answersets.destroy", "display_name" => "Delete Answer Set", "description" => "Soft delete answer sets", "permission_group" => "assessments", "guard_name" => $guardName, "created_at" => Carbon::now()->toDateTimeString()],

                    // Presets (read-only index for now)
                    ["name" => "exams.presets.index", "display_name" => "List Presets", "description" => "Access presets index/API", "permission_group" => "assessments", "guard_name" => $guardName, "created_at" => Carbon::now()->toDateTimeString()],

                    // Propagation
                    ["name" => "exams.propagate.questions", "display_name" => "Propagate Questions", "description" => "Run scoped propagation for questions", "permission_group" => "assessments", "guard_name" => $guardName, "created_at" => Carbon::now()->toDateTimeString()],
                    ["name" => "exams.propagate.exams", "display_name" => "Propagate Exams", "description" => "Run scoped propagation for exams", "permission_group" => "assessments", "guard_name" => $guardName, "created_at" => Carbon::now()->toDateTimeString()],
                    // Reviews
                    ["name" => "exams.reviews.index", "display_name" => "List Reviews", "description" => "List attempts needing manual review", "permission_group" => "assessments", "guard_name" => $guardName, "created_at" => Carbon::now()->toDateTimeString()],
                    ["name" => "exams.reviews.update", "display_name" => "Update Reviews", "description" => "Award points & finalize reviews", "permission_group" => "assessments", "guard_name" => $guardName, "created_at" => Carbon::now()->toDateTimeString()],
                    ["name" => "exams.reports.index", "display_name" => "Assessments Reports", "description" => "Access assessments reports", "permission_group" => "assessments", "guard_name" => $guardName, "created_at" => Carbon::now()->toDateTimeString()],
                    // Attempts (candidate)
                    ["name" => "exams.attempts.start", "display_name" => "Start Attempts", "description" => "Start assessments attempts", "permission_group" => "assessments", "guard_name" => $guardName, "created_at" => Carbon::now()->toDateTimeString()],
                    ["name" => "exams.attempts.view_result", "display_name" => "View Results", "description" => "View own results", "permission_group" => "assessments", "guard_name" => $guardName, "created_at" => Carbon::now()->toDateTimeString()],
                    ["name" => "exams.attempts.answer", "display_name" => "Answer Attempts", "description" => "Save answers during attempts", "permission_group" => "assessments", "guard_name" => $guardName, "created_at" => Carbon::now()->toDateTimeString()],
                    ["name" => "exams.attempts.submit", "display_name" => "Submit Attempts", "description" => "Submit attempts for grading", "permission_group" => "assessments", "guard_name" => $guardName, "created_at" => Carbon::now()->toDateTimeString()],

                    // Reports (placeholder for 1H)
                    ["name" => "exams.reports.index", "display_name" => "Assessments Reports", "description" => "View assessments reports", "permission_group" => "assessments", "guard_name" => $guardName, "created_at" => Carbon::now()->toDateTimeString()],
                    ["name" => "exams.reports.export", "display_name" => "Export Assessments Report", "description" => "Export assessments report", "permission_group" => "assessments", "guard_name" => $guardName, "created_at" => Carbon::now()->toDateTimeString()],
                ]);
            }
        }
    }
}
