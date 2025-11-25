<?php

namespace Yami\Assessments\Tests\Feature;

use Yami\Assessments\Tests\Concerns\CreatesReportData;
use Yami\Assessments\Tests\Fixtures\User;
use Yami\Assessments\Tests\TestCase;
use Illuminate\Testing\TestResponse;
use Illuminate\Support\Facades\View;

class AdminReportsTest extends TestCase
{
    use CreatesReportData;
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('assessments.enabled', true);
        config()->set('assessments.admin_only', true);
        config()->set('assessments.middleware.admin', ['web', 'auth:web']);

        View::addLocation(__DIR__ . '/../Fixtures/views');

        require dirname(__DIR__, 2) . '/routes/assessments_web.php';
    }

    public function test_reports_page_displays_metrics_and_export_links(): void
    {
        $admin = $this->makeAdminUser();
        $exam = $this->createExamWithAttempts();

        $response = $this->actingAs($admin, 'web')
            ->get('/dashboard/assessments/reports');

        $response->assertOk();
        $response->assertSee($exam->title);
        $response->assertSee('Pass Rate: 50.00%');
        $response->assertSee('Avg %: 70%');
        $response->assertSee('Export CSV');
        $response->assertSee('Export JSON');
    }

    public function test_reports_export_csv_contains_metrics_row(): void
    {
        $admin = $this->makeAdminUser('csv-admin@example.com');

        $this->createExamWithAttempts();

        $response = $this->actingAs($admin, 'web')
            ->get('/dashboard/assessments/reports/export');

        $response->assertOk();
        $this->assertStreamContains($response, 'Pass Rate (%)');
        $this->assertStreamContains($response, '50');
        $this->assertStreamContains($response, '70');
    }

    public function test_reports_export_json_returns_metrics_payload(): void
    {
        $admin = $this->makeAdminUser('json-admin@example.com');

        $this->createExamWithAttempts();

        $response = $this->actingAs($admin, 'web')
            ->getJson('/dashboard/assessments/reports/export/json');

        $response->assertOk();
        $response->assertJsonPath('data.0.metrics.total_attempts', 2);
        $response->assertJsonPath('data.0.metrics.pass_rate', 50);
        $response->assertJsonPath('data.0.metrics.average_percent', 70);
        $response->assertJsonPath('data.0.metrics.timeline.0.attempts', 2);
        $response->assertJsonPath('data.0.summary.pool_size', 2);
        $response->assertJsonStructure([
            'generated_at',
            'data' => [
                [
                    'exam' => [
                        'id',
                        'title',
                        'slug',
                        'assembly_mode',
                        'question_count',
                        'target_total_score',
                        'is_published',
                        'status',
                        'category_id',
                        'topics',
                    ],
                    'summary',
                    'metrics',
                ],
            ],
        ]);
    }

    protected function assertStreamContains(TestResponse $response, string $expected): void
    {
        $content = $response->streamedContent();
        $this->assertStringContainsString($expected, $content);
    }
}
