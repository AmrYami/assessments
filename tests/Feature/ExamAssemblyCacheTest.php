<?php

namespace Yami\Assessments\Tests\Feature;

use Yami\Assessments\Domain\Models\Exam;
use Yami\Assessments\Domain\Models\Question;
use Yami\Assessments\Services\ExamAssemblyService;
use Yami\Assessments\Services\QuestionPoolCache;
use Yami\Assessments\Tests\TestCase;
use Illuminate\Support\Facades\Cache;

class ExamAssemblyCacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config()->set('assessments.cache.pool_ttl', 600);
    }

    public function test_question_pool_cache_reuses_payload_until_flushed(): void
    {
        /** @var QuestionPoolCache $cache */
        $cache = app(QuestionPoolCache::class);

        $invocations = 0;
        $first = $cache->remember(10, [1, 2], function () use (&$invocations) {
            $invocations++;
            return [
                ['id' => 1, 'weight' => 1, 'difficulty' => 'easy'],
            ];
        });

        $second = $cache->remember(10, [1, 2], function () use (&$invocations) {
            $invocations++;
            return [
                ['id' => 2, 'weight' => 2, 'difficulty' => 'medium'],
            ];
        });

        $this->assertSame(1, $invocations, 'Resolver should run only once while cache is warm.');
        $this->assertSame($first, $second, 'Subsequent lookups should return cached payload.');

        $cache->flush();

        $cache->remember(10, [1, 2], function () use (&$invocations) {
            $invocations++;
            return [
                ['id' => 3, 'weight' => 3, 'difficulty' => 'hard'],
            ];
        });

        $this->assertSame(2, $invocations, 'Resolver should run again after cache is flushed.');
    }

    public function test_question_events_flush_pool_cache(): void
    {
        $exam = Exam::create([
            'title' => 'Cache Exam',
            'slug' => 'cache-exam',
            'assembly_mode' => 'by_count',
            'question_count' => 1,
            'status' => 'draft',
            'is_published' => false,
        ]);

        Question::create([
            'slug' => 'cache-question',
            'text' => 'Cached question',
            'response_type' => 'single_choice',
            'weight' => 1,
            'difficulty' => 'easy',
            'is_active' => true,
        ]);

        /** @var ExamAssemblyService $service */
        $service = app(ExamAssemblyService::class);
        $first = $service->buildPool($exam, []);
        $this->assertCount(1, $first);
        $this->assertNotEmpty(Cache::get('assessments:pool_keys', []), 'Pool cache key ring should have entries after initial build.');

        $newQuestion = Question::create([
            'slug' => 'flush-question-2',
            'text' => 'New question',
            'response_type' => 'single_choice',
            'weight' => 1,
            'difficulty' => 'easy',
            'is_active' => true,
        ]);

        $this->assertEmpty(Cache::get('assessments:pool_keys', []), 'Creating a question should flush cached pool keys.');

        $updated = $service->buildPool($exam, [])->pluck('id')->all();
        $this->assertCount(2, $updated);
        $this->assertContains($newQuestion->id, $updated);
    }
}
