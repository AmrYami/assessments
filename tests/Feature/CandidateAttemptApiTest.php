<?php

namespace Streaming\Assessments\Tests\Feature;

use Streaming\Assessments\Domain\Models\{Attempt, Exam};
use Streaming\Assessments\Domain\Models\{AnswerKey, Question, QuestionOption};
use Streaming\Assessments\Domain\Models\Topic;
use Streaming\Assessments\Tests\Fixtures\User;
use Streaming\Assessments\Tests\TestCase;
use Illuminate\Support\Facades\DB;

class CandidateAttemptApiTest extends TestCase
{
    public function test_candidate_can_start_manual_exam(): void
    {
        config()->set('assessments.enabled', true);
        config()->set('assessments.admin_only', false);

        $user = User::create([
            'name' => 'Candidate',
            'email' => 'candidate@example.com',
            'password' => bcrypt('secret'),
        ]);

        $topic = Topic::create([
            'name' => 'General',
            'slug' => 'general',
            'is_active' => true,
            'position' => 1,
        ]);

        $question = Question::create([
            'slug' => 'sample-question',
            'text' => 'What is 2 + 2?',
            'response_type' => 'single_choice',
            'weight' => 1,
            'difficulty' => 'easy',
            'is_active' => true,
        ]);
        $question->topics()->sync([$topic->id]);

        $exam = Exam::create([
            'title' => 'Math Basics',
            'slug' => 'math-basics',
            'assembly_mode' => 'manual',
            'status' => 'published',
            'is_published' => true,
            'max_attempts' => 2,
        ]);
        $exam->questions()->attach($question->id, ['position' => 1]);

        $this->assertNotNull($exam->id);
        $this->assertNotNull(Exam::find($exam->id));

        Attempt::create([
            'exam_id' => $exam->id,
            'user_id' => $user->id,
            'status' => 'in_progress',
            'started_at' => now(),
            'frozen_question_ids' => [$question->id],
        ])->delete();

        $response = $this->actingAs($user, 'web')
            ->postJson('/api/exams/' . $exam->id . '/attempts', []);

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'attempt_id',
                'expires_at',
                'questions' => [
                    [
                        'id',
                        'text',
                        'response_type',
                    ],
                ],
            ],
        ]);

        $payload = $response->json('data');
        $this->assertNotEmpty($payload['attempt_id']);

        $this->assertDatabaseHas('assessment_attempts', [
            'exam_id' => $exam->id,
            'user_id' => $user->id,
            'status' => 'in_progress',
        ], 'testbench');
    }

    public function test_by_count_exam_blocks_when_exposure_strict_and_only_seen_questions_exist(): void
    {
        config()->set('assessments.enabled', true);
        config()->set('assessments.admin_only', false);
        config()->set('assessments.exposure_enabled', true);
        config()->set('assessments.exposure_strict', true);

        $user = User::create([
            'name' => 'Exposure User',
            'email' => 'exposure@example.com',
            'password' => bcrypt('secret'),
        ]);

        $question = Question::create([
            'slug' => 'only-question',
            'text' => 'Seen question',
            'response_type' => 'single_choice',
            'weight' => 1,
            'difficulty' => 'easy',
            'is_active' => true,
        ]);

        $exam = Exam::create([
            'title' => 'Exposure Strict Exam',
            'slug' => 'exposure-strict-exam',
            'assembly_mode' => 'by_count',
            'question_count' => 1,
            'status' => 'published',
            'is_published' => true,
            'max_attempts' => 1,
        ]);

        DB::table('assessment_question_exposures')->insert([
            'user_id' => $user->id,
            'question_id' => $question->id,
            'seen_count' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user, 'web')
            ->postJson('/api/exams/' . $exam->id . '/attempts', []);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Not enough unseen questions available to satisfy exposure policy.',
        ]);
    }

    public function test_by_count_exam_falls_back_to_seen_questions_when_not_strict(): void
    {
        config()->set('assessments.enabled', true);
        config()->set('assessments.admin_only', false);
        config()->set('assessments.exposure_enabled', true);
        config()->set('assessments.exposure_strict', false);

        $user = User::create([
            'name' => 'Non Strict User',
            'email' => 'non-strict@example.com',
            'password' => bcrypt('secret'),
        ]);

        $question = Question::create([
            'slug' => 'seen-question',
            'text' => 'Seen question',
            'response_type' => 'single_choice',
            'weight' => 1,
            'difficulty' => 'easy',
            'is_active' => true,
        ]);

        $exam = Exam::create([
            'title' => 'Exposure Non Strict Exam',
            'slug' => 'exposure-non-strict-exam',
            'assembly_mode' => 'by_count',
            'question_count' => 1,
            'status' => 'published',
            'is_published' => true,
            'max_attempts' => 1,
        ]);

        DB::table('assessment_question_exposures')->insert([
            'user_id' => $user->id,
            'question_id' => $question->id,
            'seen_count' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user, 'web')
            ->postJson('/api/exams/' . $exam->id . '/attempts', []);

        $response->assertOk();
        $attemptId = $response->json('data.attempt_id');
        $this->assertNotEmpty($attemptId);

        $attempt = Attempt::find($attemptId);
        $this->assertSame([$question->id], $attempt->frozen_question_ids ?? []);
    }

    public function test_by_count_exam_respects_difficulty_quota(): void
    {
        config()->set('assessments.enabled', true);
        config()->set('assessments.admin_only', false);
        config()->set('assessments.assembly.strict', true);

        $user = User::create([
            'name' => 'Quota User',
            'email' => 'quota@example.com',
            'password' => bcrypt('secret'),
        ]);

        $easy = Question::create([
            'slug' => 'easy-q',
            'text' => 'Easy question',
            'response_type' => 'single_choice',
            'weight' => 1,
            'difficulty' => 'easy',
            'is_active' => true,
        ]);

        $medium = Question::create([
            'slug' => 'medium-q',
            'text' => 'Medium question',
            'response_type' => 'single_choice',
            'weight' => 1,
            'difficulty' => 'medium',
            'is_active' => true,
        ]);

        $exam = Exam::create([
            'title' => 'Quota Count Exam',
            'slug' => 'quota-count',
            'assembly_mode' => 'by_count',
            'question_count' => 2,
            'status' => 'published',
            'is_published' => true,
            'difficulty_split_json' => [
                'mode' => 'by_count',
                'version' => 1,
                'updated_at' => now()->toIso8601String(),
                'splits' => [
                    'easy' => 1,
                    'medium' => 1,
                    'hard' => 0,
                    'very_hard' => 0,
                ],
            ],
            'max_attempts' => 1,
        ]);

        $response = $this->actingAs($user, 'web')
            ->postJson('/api/exams/' . $exam->id . '/attempts', []);

        $response->assertOk();
        $attemptId = $response->json('data.attempt_id');
        $this->assertNotEmpty($attemptId);

        $attempt = Attempt::find($attemptId);
        $difficulties = Question::whereIn('id', $attempt->frozen_question_ids ?? [])->pluck('difficulty')->all();
        sort($difficulties);
        $this->assertSame(['easy', 'medium'], $difficulties);

        $failingExam = Exam::create([
            'title' => 'Impossible Quota',
            'slug' => 'quota-fail',
            'assembly_mode' => 'by_count',
            'question_count' => 2,
            'status' => 'published',
            'is_published' => true,
            'difficulty_split_json' => [
                'mode' => 'by_count',
                'version' => 1,
                'updated_at' => now()->toIso8601String(),
                'splits' => [
                    'easy' => 0,
                    'medium' => 2,
                    'hard' => 0,
                    'very_hard' => 0,
                ],
            ],
            'max_attempts' => 1,
        ]);

        $this->actingAs($user, 'web')
            ->postJson('/api/exams/' . $failingExam->id . '/attempts', [])
            ->assertStatus(422);
    }

    public function test_by_score_exam_respects_difficulty_targets(): void
    {
        config()->set('assessments.enabled', true);
        config()->set('assessments.admin_only', false);
        config()->set('assessments.assembly.strict', true);

        $user = User::create([
            'name' => 'Score User',
            'email' => 'score@example.com',
            'password' => bcrypt('secret'),
        ]);

        $easy = Question::create([
            'slug' => 'score-easy',
            'text' => 'Score easy',
            'response_type' => 'single_choice',
            'weight' => 10,
            'difficulty' => 'easy',
            'is_active' => true,
        ]);

        $hard = Question::create([
            'slug' => 'score-hard',
            'text' => 'Score hard',
            'response_type' => 'single_choice',
            'weight' => 20,
            'difficulty' => 'hard',
            'is_active' => true,
        ]);

        $exam = Exam::create([
            'title' => 'Score Quota',
            'slug' => 'score-quota',
            'assembly_mode' => 'by_score',
            'target_total_score' => 30,
            'status' => 'published',
            'is_published' => true,
            'difficulty_split_json' => [
                'mode' => 'by_score',
                'version' => 1,
                'updated_at' => now()->toIso8601String(),
                'splits' => [
                    'easy' => 10,
                    'medium' => 0,
                    'hard' => 20,
                    'very_hard' => 0,
                ],
            ],
            'max_attempts' => 1,
        ]);

        $response = $this->actingAs($user, 'web')
            ->postJson('/api/exams/' . $exam->id . '/attempts', []);
        $response->assertOk();
        $ids = Attempt::find($response->json('data.attempt_id'))->frozen_question_ids;
        sort($ids);
        $this->assertSame([$easy->id, $hard->id], $ids);

        $failingExam = Exam::create([
            'title' => 'Score Impossible',
            'slug' => 'score-impossible',
            'assembly_mode' => 'by_score',
            'target_total_score' => 30,
            'status' => 'published',
            'is_published' => true,
            'difficulty_split_json' => [
                'mode' => 'by_score',
                'version' => 1,
                'updated_at' => now()->toIso8601String(),
                'splits' => [
                    'easy' => 30,
                    'medium' => 0,
                    'hard' => 0,
                    'very_hard' => 0,
                ],
            ],
            'max_attempts' => 1,
        ]);

        $this->actingAs($user, 'web')
            ->postJson('/api/exams/' . $failingExam->id . '/attempts', [])
            ->assertStatus(422);
    }

    public function test_by_score_exam_allows_tolerance_when_non_strict(): void
    {
        config()->set('assessments.enabled', true);
        config()->set('assessments.admin_only', false);
        config()->set('assessments.assembly.strict', false);

        $user = User::create([
            'name' => 'Tolerance User',
            'email' => 'tolerance@example.com',
            'password' => bcrypt('secret'),
        ]);

        $q1 = Question::create([
            'slug' => 'tol-q1',
            'text' => 'First question',
            'response_type' => 'single_choice',
            'weight' => 10,
            'difficulty' => 'easy',
            'is_active' => true,
        ]);

        $q2 = Question::create([
            'slug' => 'tol-q2',
            'text' => 'Second question',
            'response_type' => 'single_choice',
            'weight' => 15,
            'difficulty' => 'medium',
            'is_active' => true,
        ]);

        $exam = Exam::create([
            'title' => 'Tolerance Exam',
            'slug' => 'tolerance-exam',
            'assembly_mode' => 'by_score',
            'target_total_score' => 30,
            'status' => 'published',
            'is_published' => true,
            'max_attempts' => 1,
        ]);

        $response = $this->actingAs($user, 'web')
            ->postJson('/api/exams/' . $exam->id . '/attempts', []);

        $response->assertOk();
        $attemptId = $response->json('data.attempt_id');
        $this->assertNotEmpty($attemptId);
        $attempt = Attempt::find($attemptId);
        $this->assertNotNull($attempt);
        $selected = $attempt->frozen_question_ids ?? [];
        sort($selected);
        $expected = [$q1->id, $q2->id];
        sort($expected);
        $this->assertSame($expected, $selected);

        config()->set('assessments.assembly.strict', true);
        $examStrict = Exam::create([
            'title' => 'Strict Exam',
            'slug' => 'strict-exam',
            'assembly_mode' => 'by_score',
            'target_total_score' => 30,
            'status' => 'published',
            'is_published' => true,
            'max_attempts' => 1,
        ]);

        $this->actingAs($user, 'web')
            ->postJson('/api/exams/' . $examStrict->id . '/attempts', [])
            ->assertStatus(422);
    }

    public function test_attempt_details_include_explanations_when_exam_enables_them(): void
    {
        config()->set('assessments.enabled', true);
        config()->set('assessments.admin_only', false);
        config()->set('assessments.assembly.strict', true);

        $user = User::create([
            'name' => 'Explain User',
            'email' => 'explain@example.com',
            'password' => bcrypt('secret'),
        ]);

        $question = Question::create([
            'slug' => 'math-explain',
            'text' => 'What is 2 + 2?',
            'response_type' => 'single_choice',
            'weight' => 10,
            'difficulty' => 'easy',
            'is_active' => true,
            'explanation' => 'Because 2 + 2 equals 4.',
        ]);

        $correctOption = QuestionOption::create([
            'question_id' => $question->id,
            'label' => '4',
            'key' => 'A',
            'position' => 1,
            'is_active' => true,
        ]);

        QuestionOption::create([
            'question_id' => $question->id,
            'label' => '5',
            'key' => 'B',
            'position' => 2,
            'is_active' => true,
        ]);

        AnswerKey::create([
            'question_id' => $question->id,
            'option_id' => $correctOption->id,
        ]);

        $exam = Exam::create([
            'title' => 'Explain Exam',
            'slug' => 'explain-exam',
            'assembly_mode' => 'manual',
            'status' => 'published',
            'is_published' => true,
            'question_count' => 1,
            'show_explanations' => true,
            'max_attempts' => 1,
        ]);
        $exam->questions()->attach($question->id, ['position' => 1]);

        $start = $this->actingAs($user, 'web')
            ->postJson('/api/exams/' . $exam->id . '/attempts', []);
        $start->assertOk();
        $attemptId = $start->json('data.attempt_id');
        $this->assertNotEmpty($attemptId);

        $this->actingAs($user, 'web')
            ->patchJson('/api/attempts/' . $attemptId . '/answers', [
                'answers' => [
                    [
                        'question_id' => $question->id,
                        'option_ids' => [$correctOption->id],
                    ],
                ],
            ])
            ->assertOk();

        $submit = $this->actingAs($user, 'web')
            ->postJson('/api/attempts/' . $attemptId . '/submit', []);
        $submit->assertOk();
        $submitDetails = $submit->json('data.details');
        $this->assertSame('Because 2 + 2 equals 4.', $submitDetails[0]['explanation']);

        $result = $this->actingAs($user, 'web')
            ->getJson('/api/attempts/' . $attemptId . '/result');
        $result->assertOk();
        $this->assertSame('Because 2 + 2 equals 4.', $result->json('data.details.0.explanation'));

        $exam->update(['show_explanations' => false]);
        $resultNoExplain = $this->actingAs($user, 'web')
            ->getJson('/api/attempts/' . $attemptId . '/result');
        $resultNoExplain->assertOk();
        $this->assertArrayNotHasKey('explanation', $resultNoExplain->json('data.details.0'));
    }
}
