<?php

namespace Streaming\Assessments\Tests\Feature;

use Streaming\Assessments\Tests\Concerns\CreatesReportData;
use Streaming\Assessments\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;

class AdminReportsCommandTest extends TestCase
{
    use CreatesReportData;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('assessments.enabled', true);
        config()->set('assessments.admin_only', true);
        $this->createExamWithAttempts();
    }

    public function test_assessments_reports_command_outputs_table(): void
    {
        $exitCode = Artisan::call('assessments:reports');
        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Health & Safety', Artisan::output());
    }

    public function test_assessments_reports_command_supports_json_export(): void
    {
        $path = storage_path('app/cli-report.json');
        @unlink($path);

        $exitCode = Artisan::call('assessments:reports', [
            '--format' => 'json',
            '--path' => $path,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertFileExists($path);
        $json = json_decode(file_get_contents($path), true);
        $this->assertSame('Health & Safety', $json['data'][0]['exam']['title']);
    }
}
