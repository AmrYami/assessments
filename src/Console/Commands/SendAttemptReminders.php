<?php

namespace Yami\Assessments\Console\Commands;

use Yami\Assessments\Domain\Models\Attempt;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendAttemptReminders extends Command
{
    protected $signature = 'assessments:attempts:remind {--hours=24 : Hours since start before reminding} {--dry-run : Output matches without updating anything}';

    protected $description = 'Notify admins about in-progress attempts that are overdue or nearing expiry.';

    public function handle(): int
    {
        $thresholdHours = max(1, (int) $this->option('hours'));
        $cutoff = now()->subHours($thresholdHours);

        $query = Attempt::query()
            ->where('status', 'in_progress')
            ->where(function ($q) use ($cutoff) {
                $q->whereNull('expires_at')->where('started_at', '<=', $cutoff)
                    ->orWhere('expires_at', '<=', now())
                    ->orWhere('review_status', 'pending');
            })
            ->orderBy('started_at');

        $count = $query->count();

        if ($count === 0) {
            $this->info('No overdue attempts found.');
            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $this->info("{$count} attempt(s) require attention:");

        $query->chunkById(50, function ($attempts) use ($cutoff, $dryRun) {
            foreach ($attempts as $attempt) {
                $message = sprintf(
                    'Attempt #%d for exam %d (user %d) started %s%s',
                    $attempt->id,
                    $attempt->exam_id,
                    $attempt->user_id,
                    optional($attempt->started_at)->diffForHumans(),
                    $attempt->expires_at ? ' – expires ' . optional($attempt->expires_at)->diffForHumans() : ''
                );

                $this->line(' • ' . $message);

                if (!$dryRun) {
                    Log::channel(config('assessments.reminder.log_channel', 'daily'))->info('[Assessments] Attempt reminder', [
                        'attempt_id' => $attempt->id,
                        'exam_id' => $attempt->exam_id,
                        'user_id' => $attempt->user_id,
                        'started_at' => $attempt->started_at,
                        'expires_at' => $attempt->expires_at,
                        'review_status' => $attempt->review_status,
                    ]);
                }
            }
        });

        if ($dryRun) {
            $this->comment('Dry run complete. No notifications were sent.');
        }

        return self::SUCCESS;
    }
}
