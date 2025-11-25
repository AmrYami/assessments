<?php

namespace Yami\Assessments\Tests\Feature;

use Yami\Assessments\Domain\Models\Exam;
use Yami\Assessments\Tests\Concerns\CreatesReportData;
use Yami\Assessments\Tests\TestCase;
use Illuminate\Support\Facades\View;

class AdminReportsApiTest extends TestCase
{
    use CreatesReportData;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('assessments.enabled', true);
        config()->set('assessments.admin_only', true);
        config()->set('assessments.middleware.admin_api', ['web', 'auth:web']);
        View::addLocation(__DIR__ . '/../Fixtures/views');
        require dirname(__DIR__, 2) . '/routes/assessments_api.php';
    }

    public function test_admin_reports_api_returns_snapshot(): void
    {
        $admin = $this->makeAdminUser();
        $exam = $this->createExamWithAttempts();

        $response = $this->actingAs($admin, 'web')
            ->getJson('/dashboard/assessments/api/reports');

        $response->assertOk();
        $response->assertJsonPath('data.0.exam.title', $exam->title);
        $response->assertJsonPath('data.0.metrics.total_attempts', 2);
        $response->assertJsonStructure([
            'generated_at',
            'data' => [
                [
                    'exam' => [
                        'id', 'title', 'slug', 'topics',
                    ],
                    'summary',
                    'metrics' => [
                        'total_attempts',
                        'timeline',
                    ],
                ],
            ],
        ]);
    }

    public function test_admin_reports_api_can_return_single_exam(): void
    {
        $admin = $this->makeAdminUser('api-single@example.com');
        $exam = $this->createExamWithAttempts();

        $response = $this->actingAs($admin, 'web')
            ->getJson('/dashboard/assessments/api/reports/' . $exam->id);

        $response->assertOk();
        $response->assertJsonPath('data.exam.id', $exam->id);
        $response->assertJsonPath('data.metrics.total_attempts', 2);
    }
}
