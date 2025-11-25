<?php

namespace Amryami\Assessments\Console\Commands;

use Amryami\Assessments\Domain\Models\{Answer, AnswerUsageAggregate};
use Amryami\Assessments\Services\AnswerUsageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RebuildAnswerUsage extends Command
{
    protected $signature = 'assessments:rebuild-answer-usage
        {--answer-id= : Rebuild a specific answer}
        {--chunk=1000 : Batch size}
        {--with-trashed : Include soft-deleted links}
        {--since= : Only answers changed since YYYY-MM-DD}
        {--full : Full rebuild (ignore since)}
        {--dry-run : Do not write changes}';

    protected $description = 'Rebuild Answer usage aggregates (nightly/cron-friendly)';

    public function handle(AnswerUsageService $svc): int
    {
        $chunk = max(1, (int) ($this->option('chunk') ?: 1000));
        $id = $this->option('answer-id');
        $since = $this->option('full') ? null : $this->option('since');
        $dry = (bool) $this->option('dry-run');
        $withTrashed = (bool) $this->option('with-trashed');

        $query = Answer::query();
        if ($id) {
            $query->where('id', (int) $id);
        }
        if ($since) {
            $query->where('updated_at', '>=', $since);
        }
        if ($withTrashed && method_exists(Answer::class, 'bootSoftDeletes')) {
            $query->withTrashed();
        }

        $processed = $updated = $errors = 0;
        $this->info('Rebuilding answer usage aggregates...');
        $query->orderBy('id')->chunk($chunk, function ($rows) use ($svc, $dry, &$processed, &$updated, &$errors) {
            foreach ($rows as $row) {
                $processed++;
                try {
                    if (!$dry) {
                        $this->rebuildOne($svc, (int) $row->id);
                    }
                    $updated++;
                } catch (\Throwable $e) {
                    $errors++;
                    $this->error('Failed answer #' . $row->id . ': ' . $e->getMessage());
                }
            }
        });
        $this->line("Processed={$processed} Updated={$updated} Errors={$errors}");
        return $errors ? self::FAILURE : self::SUCCESS;
    }

    protected function rebuildOne(AnswerUsageService $svc, int $answerId): void
    {
        // Reuse the recalc() logic for everything except attempts; then compute attempts via set query
        $svc->recalc($answerId);
        // Attempts
        $questions = DB::table('assessment_question_answers')
            ->whereNull('deleted_at')
            ->where('answer_id', $answerId)
            ->pluck('question_id');
        if ($questions->isEmpty()) {
            AnswerUsageAggregate::updateOrCreate(
                ['answer_id' => $answerId],
                [
                    'used_by_attempts_count' => 0,
                    'last_used_at' => null,
                    'last_recomputed_at' => now(),
                ]
            );
            return;
        }
        $attempts = DB::table('assessment_attempt_answers as aa')
            ->join('assessment_attempts as at', 'at.id', '=', 'aa.attempt_id')
            ->whereIn('aa.question_id', $questions->unique()->values()->all())
            ->selectRaw('COUNT(DISTINCT aa.attempt_id) as cnt, MAX(at.updated_at) as last')
            ->first();
        AnswerUsageAggregate::updateOrCreate(
            ['answer_id' => $answerId],
            [
                'used_by_attempts_count' => (int) ($attempts->cnt ?? 0),
                'last_used_at' => $attempts->last,
                'last_recomputed_at' => now(),
            ]
        );
    }
}
