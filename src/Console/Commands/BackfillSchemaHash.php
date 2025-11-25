<?php

namespace Yami\Assessments\Console\Commands;

use Yami\Assessments\Domain\Models\{Exam, Question};
use Yami\Assessments\Services\SchemaHashService;
use Illuminate\Console\Command;

class BackfillSchemaHash extends Command
{
    protected $signature = 'assessments:backfill-schema-hash
        {--only=all : questions|exams|all}
        {--chunk=1000 : Batch size}
        {--dry-run : Do not write changes}
        {--where-updated-after= : ISO date; limit to updated records}
        {--with-trashed : Include soft-deleted rows}';

    protected $description = 'Compute and persist schema_hash for Questions/Exams';

    public function handle(SchemaHashService $svc): int
    {
        $only = $this->option('only') ?: 'all';
        $chunk = max(1, (int) $this->option('chunk'));
        $dry = (bool) $this->option('dry-run');
        $after = $this->option('where-updated-after');
        $withTrashed = (bool) $this->option('with-trashed');
        $totalProcessed = $totalUpdated = $skipped = $errors = 0;

        $run = function ($model, $label) use ($svc, $chunk, $dry, $after, $withTrashed, &$totalProcessed, &$totalUpdated, &$skipped, &$errors) {
            $query = $model::query();
            if (method_exists($model, 'bootSoftDeletes') && !$withTrashed) {
                // default excludes trashed
            } elseif (method_exists($model, 'bootSoftDeletes') && $withTrashed) {
                $query = $query->withTrashed();
            }
            if ($after) {
                $query->where('updated_at', '>=', $after);
            }
            $this->info("Processing {$label} in chunks of {$chunk}...");
            $query->orderBy('id')->chunk($chunk, function ($rows) use ($svc, $dry, &$totalProcessed, &$totalUpdated, &$skipped, &$errors, $label) {
                foreach ($rows as $row) {
                    try {
                        $totalProcessed++;
                        $hash = $row instanceof Question
                            ? $svc->computeForQuestion($row)
                            : $svc->computeForExam($row);
                        if ($row->schema_hash === $hash) {
                            $skipped++;
                            continue;
                        }
                        if ($dry) {
                            $totalUpdated++;
                            continue;
                        }
                        $row->schema_hash = $hash;
                        $row->save();
                        $totalUpdated++;
                    } catch (\Throwable $e) {
                        $errors++;
                        $this->error("{$label} #{$row->id} failed: " . $e->getMessage());
                    }
                }
            });
        };

        if ($only === 'questions' || $only === 'all') {
            $run(Question::class, 'Question');
        }
        if ($only === 'exams' || $only === 'all') {
            $run(Exam::class, 'Exam');
        }

        $this->line("Processed={$totalProcessed} Updated={$totalUpdated} Skipped={$skipped} Errors={$errors}");
        return $errors ? self::FAILURE : self::SUCCESS;
    }
}
