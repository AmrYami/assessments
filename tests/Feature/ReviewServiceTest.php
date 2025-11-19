<?php

namespace Fakeeh\Assessments\Tests\Feature;

use Fakeeh\Assessments\Contracts\ReviewServiceInterface;
use Fakeeh\Assessments\Domain\Models\{Attempt, Exam, ExamRequirement, Question};
use Fakeeh\Assessments\Tests\Fixtures\User;
use Fakeeh\Assessments\Tests\TestCase;
use Illuminate\Support\Facades\DB;

class ReviewServiceTest extends TestCase
{
    public function test_review_service_finalizes_attempt_and_updates_requirement(): void
    {
        $exam = Exam::create([
            'title' => 'Service Exam',
            'slug' => 'service-exam',
            'assembly_mode' => 'manual',
            'status' => 'draft',
            'is_published' => false,
            'pass_type' => 'percent',
            'pass_value' => 35,
            'max_attempts' => 2,
        ]);

        $question = Question::create([
            'slug' => 'service-question',
            'text' => 'Explain your reasoning',
            'response_type' => 'text',
            'weight' => 40,
            'difficulty' => 'medium',
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'Candidate',
            'email' => 'candidate@example.com',
            'password' => bcrypt('password'),
        ]);

        $attempt = Attempt::create([
            'exam_id' => $exam->id,
            'user_id' => $user->id,
            'status' => 'in_progress',
            'review_status' => 'pending',
            'frozen_question_ids' => [$question->id],
            'score_auto' => 10,
            'score_manual' => 0,
            'total_score' => 10,
            'percent' => 10,
            'passed' => false,
        ]);
        $attempt->forceFill(['score_max' => 100])->save();

        DB::table('assessment_attempt_answers')->insert([
            'attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'option_ids' => json_encode([]),
            'needs_review' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        ExamRequirement::create([
            'user_id' => $user->id,
            'exam_id' => $exam->id,
            'status' => 'pending',
            'attempts_used' => 0,
            'max_attempts' => 2,
        ]);

        $service = app(ReviewServiceInterface::class);
        $service->apply($attempt, [
            ['question_id' => $question->id, 'awarded_score' => 30],
        ], true, 'Reviewed by service', 42);

        $attempt->refresh();
        $requirement = ExamRequirement::where('user_id', $user->id)->where('exam_id', $exam->id)->first();

        $this->assertSame(30, $attempt->score_manual);
        $this->assertSame(40, $attempt->total_score);
        $this->assertSame(40, $attempt->percent);
        $this->assertTrue($attempt->passed);
        $this->assertEquals('completed', $attempt->review_status);
        $this->assertEquals('Reviewed by service', $attempt->review_notes);
        $this->assertEquals(42, $attempt->reviewer_id);
        $this->assertNotNull($attempt->reviewed_at);
        $this->assertNotNull($requirement);
        $this->assertEquals('passed', $requirement->status);

        $storedScore = DB::table('assessment_attempt_answers')
            ->where('attempt_id', $attempt->id)
            ->where('question_id', $question->id)
            ->value('awarded_score');

        $this->assertSame(30, $storedScore);
    }
}

