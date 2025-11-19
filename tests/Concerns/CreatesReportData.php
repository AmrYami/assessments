<?php

namespace Fakeeh\Assessments\Tests\Concerns;

use Fakeeh\Assessments\Domain\Models\Attempt;
use Fakeeh\Assessments\Domain\Models\Exam;
use Fakeeh\Assessments\Domain\Models\Question;
use Fakeeh\Assessments\Tests\Fixtures\User;

trait CreatesReportData
{
    protected function createExamWithAttempts(): Exam
    {
        $exam = Exam::create([
            'title' => 'Health & Safety',
            'slug' => 'health-safety',
            'assembly_mode' => 'by_count',
            'question_count' => 2,
            'status' => 'published',
            'is_published' => true,
            'max_attempts' => 3,
        ]);

        Question::create([
            'slug' => 'safety-q1',
            'text' => 'First safety question',
            'response_type' => 'single_choice',
            'weight' => 10,
            'difficulty' => 'easy',
            'is_active' => true,
        ]);

        Question::create([
            'slug' => 'safety-q2',
            'text' => 'Second safety question',
            'response_type' => 'single_choice',
            'weight' => 20,
            'difficulty' => 'medium',
            'is_active' => true,
        ]);

        $candidate = User::create([
            'name' => 'Candidate A',
            'email' => 'candidate-a@example.com',
            'password' => bcrypt('secret'),
        ]);

        Attempt::create([
            'exam_id' => $exam->id,
            'user_id' => $candidate->id,
            'status' => 'submitted',
            'started_at' => now()->subHour(),
            'total_score' => 15,
            'percent' => 80,
            'passed' => true,
        ]);

        Attempt::create([
            'exam_id' => $exam->id,
            'user_id' => $candidate->id,
            'status' => 'submitted',
            'started_at' => now()->subMinutes(30),
            'total_score' => 10,
            'percent' => 60,
            'passed' => false,
        ]);

        return $exam;
    }

    protected function makeAdminUser(string $email = 'admin@example.com'): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => $email,
            'password' => bcrypt('secret'),
        ]);
    }
}
