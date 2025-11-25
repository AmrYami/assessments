<?php

namespace Yami\Assessments\Tests\Feature;

use Yami\Assessments\Domain\Models\Attempt;
use Yami\Assessments\Tests\Fixtures\User;
use Yami\Assessments\Tests\TestCase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SendAttemptRemindersCommandTest extends TestCase
{
    public function test_reminder_command_outputs_overdue_attempts(): void
    {
        $user = User::create([
            'name' => 'Reminder User',
            'email' => 'reminder@example.com',
            'password' => bcrypt('password'),
        ]);

        $attempt = Attempt::create([
            'exam_id' => 1,
            'user_id' => $user->id,
            'status' => 'in_progress',
            'started_at' => now()->subHours(48),
            'review_status' => 'pending',
        ]);

        $this->artisan('assessments:attempts:remind', ['--hours' => 24, '--dry-run' => true])
            ->expectsOutputToContain((string) $attempt->id)
            ->expectsOutput('Dry run complete. No notifications were sent.')
            ->assertExitCode(0);
    }
}

