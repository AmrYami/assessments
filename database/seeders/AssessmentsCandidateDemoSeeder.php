<?php

namespace Fakeeh\Assessments\Database\Seeders;

use Fakeeh\Assessments\Support\ModelResolver;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AssessmentsCandidateDemoSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Ensure a demo candidate exists
        $email = 'demo.candidate@amryami.com';
        $userId = (int) (DB::table('users')->where('email', $email)->value('id') ?: 0);
        if (!$userId) {
            $userModel = ModelResolver::user();
            if (!method_exists($userModel, 'factory')) {
                $this->command?->warn('User factory not available; skipping demo candidate seeder.');
                return;
            }
            /** @var \Illuminate\Database\Eloquent\Model $user */
            $user = $userModel::factory()->create(['email' => $email]);
            $userId = (int) $user->id;
        }

        // Pick the manual Demo Exam
        $exam = DB::table('assessment_exams')->where('slug', 'demo-exam')->first();
        if (!$exam) {
            $this->command?->warn('Demo Exam not found. Run AssessmentsDemoSeeder first.');
            return;
        }

        // Compose frozen questions from manual placements
        $qids = DB::table('assessment_exam_questions')->where('exam_id', $exam->id)->orderBy('position')->pluck('question_id')->all();
        if (empty($qids)) {
            $this->command?->warn('Demo Exam has no questions.');
            return;
        }

        // Build answers with correct option_ids per question
        $totalScore = 0;
        $answers = [];
        foreach ($qids as $qid) {
            $totalScore += (int) DB::table('assessment_questions')->where('id', $qid)->value('weight');
            $correctOptionId = (int) (DB::table('assessment_answer_keys')->where('question_id', $qid)->value('option_id') ?: 0);
            if ($correctOptionId) {
                $answers[] = ['question_id' => $qid, 'option_ids' => json_encode([$correctOptionId])];
            } else {
                $optId = (int) DB::table('assessment_question_options')->where('question_id', $qid)->orderBy('position')->value('id');
                $answers[] = ['question_id' => $qid, 'option_ids' => json_encode([$optId])];
            }
        }

        $score = $totalScore; // full marks
        $percent = $totalScore > 0 ? (int) round(($score / $totalScore) * 100) : 0;
        $passed = true;

        // Create attempt
        $attemptId = (int) DB::table('assessment_attempts')->insertGetId([
            'exam_id' => $exam->id,
            'user_id' => $userId,
            'status' => 'submitted',
            'started_at' => $now->copy()->subMinutes(15),
            'expires_at' => $now->copy()->addMinutes(45),
            'seed' => 12345,
            'frozen_question_ids' => json_encode($qids),
            'total_score' => $score,
            'percent' => $percent,
            'passed' => $passed,
            'result_json' => json_encode([
                'total_possible' => $totalScore,
                'details' => array_map(function ($row) {
                    return [
                        'question_id' => $row['question_id'],
                        'selected' => json_decode($row['option_ids'], true),
                        'correct' => true,
                        'weight' => (int) DB::table('assessment_questions')->where('id', $row['question_id'])->value('weight'),
                    ];
                }, $answers),
            ]),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        foreach ($answers as $row) {
            DB::table('assessment_attempt_answers')->insert([
                'attempt_id' => $attemptId,
                'question_id' => $row['question_id'],
                'option_ids' => $row['option_ids'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->command?->info('Assessments candidate demo: attempt submitted for Demo Exam by demo.candidate@amryami.com (password: password).');
    }
}
