<?php

namespace Streaming\Assessments\Tests\Feature;

use Streaming\Assessments\Domain\Models\Topic;
use Streaming\Assessments\Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MigrationsTest extends TestCase
{
    public function test_package_migrations_register_tables(): void
    {
        $this->assertTrue(Schema::hasTable('assessment_topics'));
        $this->assertTrue(Schema::hasTable('assessment_questions'));
        $this->assertTrue(Schema::hasTable('assessment_exams'));
    }

    public function test_topics_can_be_created_after_migration(): void
    {
        $topic = Topic::create([
            'name' => 'Demo Topic',
            'slug' => Str::slug('Demo Topic'),
            'description' => 'Topic created during package testbench run.',
            'is_active' => true,
            'position' => 1,
        ]);

        $this->assertDatabaseHas('assessment_topics', [
            'id' => $topic->id,
            'slug' => 'demo-topic',
        ], 'testbench');
    }
}
